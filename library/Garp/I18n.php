<?php
/**
 * Garp_I18n
 * Wrapper around various i18n related functionality.
 *
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_I18n {
    /**
     * Return the current locale
     *
     * @return string
     */
    public static function getCurrentLocale() {
        if (!Zend_Registry::isRegistered('Zend_Locale')) {
            throw new Garp_I18n_Exception('Zend_Locale is not registered in Zend_Registry.');
        }
        return Zend_Registry::get('Zend_Locale')->getLanguage();
    }

    /**
     * Return the default locale (as defined in application.ini)
     *
     * @return string
     */
    public static function getDefaultLocale() {
        // Try configured default first
        if (isset(Zend_Registry::get('config')->resources->locale->default)) {
            return Zend_Registry::get('config')->resources->locale->default;
        }
        // See if Zend_Locale might know the default
        if (!Zend_Registry::isRegistered('Zend_Locale')) {
            throw new Garp_I18n_Exception('Zend_Locale is not registered in Zend_Registry.');
        }
        $locale = Zend_Registry::get('Zend_Locale');
        $default = $locale->getDefault();
        if ($default) {
            $keys = array_keys($default);
            $default = current($keys);
        }
        return $default;
    }

    /**
     * Return a list of all possible locales
     *
     * @return array
     */
    public static function getLocales() {
        return Zend_Controller_Front::getInstance()->getParam('locales');
    }

    /**
     * Generate localized versions of routes
     *
     * @param array $routes The originals
     * @param array $locales
     * @return array
     */
    public static function getLocalizedRoutes(array $routes, array $locales) {
        $localizedRoutes = [];
        $defaultLocale = self::getDefaultLocale();
        $requiredLocalesRegex = '^(' . implode('|', $locales) . ')$';

        foreach ($routes as $key => $value) {
            // First let's add the default locale to this routes defaults.
            $defaults = $value['defaults'] ?? [];

            // Always default all routes to the Zend_Locale default
            $value['defaults'] = array_merge(['locale' => $defaultLocale ], $defaults);

            //$routes[$key] = $value;

            // Get our route and make sure to remove the first forward slash
            // since it's not needed.
            $routeString = $value['route'];
            $routeString = ltrim((string) $routeString, '/\\');

            // Modify our normal route to have the locale parameter.
            if (!isset($value['type']) || $value['type'] === 'Zend_Controller_Router_Route') {
                $value['route'] = ':locale/' . $routeString;
                $value['reqs']['locale'] = $requiredLocalesRegex;
                $localizedRoutes['locale_' . $key] = $value;
            } else if ($value['type'] === 'Zend_Controller_Router_Route_Regex') {
                $value['route'] = '(' . implode('|', $locales) . ')\/' . $routeString;

                // Since we added the local regex match, we need to bump the existing
                // match numbers plus one.
                $map = $value['map'] ?? [];
                foreach ($map as $index => $word) {
                    unset($map[$index++]);
                    $map[$index] = $word;
                }

                // Add our locale map
                $map[1] = 'locale';
                ksort($map);

                $value['map'] = $map;

                $localizedRoutes['locale_' . $key] = $value;
            } elseif ($value['type'] === 'Zend_Controller_Router_Route_Static') {
                foreach ($locales as $locale) {
                    $value['route'] = $locale . '/' . $routeString;
                    $value['defaults']['locale'] = $locale;
                    $routes['locale_' . $locale . '_' . $key] = $value;
                }
            }
        }
        return $localizedRoutes;
    }

    /**
     * Go from language to territory
     *
     * @param string $lang
     * @return string
     */
    public static function languageToTerritory($lang) {
        $config = Zend_Registry::get('config');
        $territory = $config->resources->locale->territories->{$lang} ?? Zend_Locale::getLocaleToTerritory($lang);
        return $territory;
    }

    /**
     * Create a Zend_Translate instance for the given locale.
     *
     * @param Zend_Locale $locale
     * @return Zend_Translate
     */
    public static function getTranslateByLocale(Zend_Locale $locale) {
        $adapterParams = [
            'locale' => $locale,
            'disableNotices' => true,
            'scan' => Zend_Translate::LOCALE_FILENAME,
            // Argh: the 'content' key is necessary in order to load the actual data,
            // even when using an adapter that ignores it.
            'content' => '!'
        ];

        // Figure out which adapter to use
        $translateAdapter = 'array';
        $config = Zend_Registry::get('config');
        if (!empty($config->resources->locale->translate->adapter)) {
            $translateAdapter = $config->resources->locale->translate->adapter;
        }
        $adapterParams['adapter'] = $translateAdapter;

        // Some additional configuration for the array adapter
        if ($translateAdapter == 'array') {
            $language = $locale->getLanguage();
            // @todo Move this to applciation.ini?
            $adapterParams['content'] = APPLICATION_PATH . '/data/i18n/' . $language . '.php';

            // Turn on caching
            if (Zend_Registry::isRegistered('CacheFrontend')) {
                $adapterParams['cache'] = Zend_Registry::get('CacheFrontend');
            }

        }

        $translate = new Zend_Translate($adapterParams);
        return $translate;
    }
}
