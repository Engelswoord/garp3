<?php
/**
 * Garp_Cli_Command_S3
 * Wrapper around awscmd, specific to s3 and s3api commands.
 *
 * @package Garp_Cli_Command
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_S3 extends Garp_Cli_Command_Aws {

    /**
     * Create a new bucket on S3.
     * This defaults to the configured bucket.
     *
     * @param array $args
     * @return bool
     */
    public function makeBucket(array $args = []) {
        $bucket = $args[0] ?? null;
        if (is_null($bucket)) {
            $config = Zend_Registry::get('config');
            $bucket = $config->cdn->s3->bucket ?? null;
        }
        if (is_null($bucket)) {
            Garp_Cli::errorOut('No bucket configured');
            return false;
        }
        return $this->s3(
            'mb', [
            's3://' . $bucket
            ]
        );
    }

    /**
     * Alias for makeBucket
     *
     * @param array $args
     * @return bool
     */
    public function mb(array $args = []) {
        return $this->makeBucket($args);
    }

    public function ls(array $args = []) {
        if (empty(Zend_Registry::get('config')->cdn->s3->bucket)) {
            Garp_Cli::errorOut('No bucket configured');
            return false;
        }
        $path = rtrim('s3://' . Zend_Registry::get('config')->cdn->s3->bucket, DIRECTORY_SEPARATOR);
        if (isset($args[0])) {
            $path .= DIRECTORY_SEPARATOR . $args[0];
        }
        $args = [$path];
        return $this->s3('ls', $args);
    }

    public function cp(array $args = []) {
        return $this->s3('cp', $args);
    }

    public function mv(array $args = []) {
        return $this->s3('mv', $args);
    }

    public function sync(array $args = []) {
        return $this->s3('sync', $args);
    }

    public function rm(array $args = []) {
        if (empty(Zend_Registry::get('config')->cdn->s3->bucket)) {
            Garp_Cli::errorOut('No bucket configured');
            return false;
        }

        if (empty($args)) {
            Garp_Cli::errorOut('No path given.');
            return false;
        }

        $path = rtrim('s3://' . Zend_Registry::get('config')->cdn->s3->bucket, DIRECTORY_SEPARATOR);
        if (isset($args[0])) {
            $path .= DIRECTORY_SEPARATOR . $args[0];
        }
        if (!Garp_Cli::confirm(
            'Are you sure you want to permanently remove ' .
            $path . '?'
        )) {
            Garp_Cli::lineOut('Changed your mind, huh? Not deleting.');
            return true;
        }
        $args = [$path];
        return $this->s3('rm', $args);
    }

    /**
     * Set CORS settings on bucket
     *
     * @return bool
     * @todo Make CORS options configurable? Do you ever want full-fletched control over these?
     */
    public function setCors() {
        $corsConfig = [
            'CORSRules' => [[
                'AllowedHeaders' => ['*'],
                'AllowedMethods' => ['GET'],
                'AllowedOrigins' => ['*'],
            ]]
        ];
        $args = [
            'bucket' => Zend_Registry::get('config')->cdn->s3->bucket,
            'cors-configuration' => "'" . json_encode($corsConfig) . "'"
        ];
        return $this->s3api('put-bucket-cors', $args);
    }

    public function getCors() {
        $args = [
            'bucket' => Zend_Registry::get('config')->cdn->s3->bucket,
        ];
        return $this->s3api('get-bucket-cors', $args);
    }

    public function setWebsiteConfiguration() {
        $websiteConfig = [
            'RoutingRules' => $this->_getWebsiteRoutingRules(),
            'IndexDocument' => [
                'Suffix' => 'index.html' // @todo Is this right? It probably never exists...
            ],
            'ErrorDocument' => [
                'Key' => 'error.html'
            ]
        ];
        $args = [
            'bucket' => Zend_Registry::get('config')->cdn->s3->bucket,
            'website-configuration' => "'" . json_encode($websiteConfig) . "'"
        ];
        return $this->s3api('put-bucket-website', $args);
    }

    public function getWebsiteConfiguration() {
        $args = [
            'bucket' => Zend_Registry::get('config')->cdn->s3->bucket,
        ];
        return $this->s3api('get-bucket-website', $args);
    }

    #[\Override]
    public function help() {
        parent::help();

        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Create a bucket:');
        Garp_Cli::lineOut(' g s3 mb my_bucket', Garp_Cli::BLUE);
        Garp_Cli::lineOut('View a directory listing:');
        Garp_Cli::lineOut(' g s3 ls uploads/images/', Garp_Cli::BLUE);
        Garp_Cli::lineOut('Remove a file:');
        Garp_Cli::lineOut(' g s3 rm uploads/images/embarrassing_photo.jpg', Garp_Cli::BLUE);
        Garp_Cli::lineOut('Get CORS configuration:');
        Garp_Cli::lineOut(' g s3 getCors', Garp_Cli::BLUE);
        Garp_Cli::lineOut('Set CORS configuration:');
        Garp_Cli::lineOut(' g s3 setCors', Garp_Cli::BLUE);
    }

    protected function _getWebsiteRoutingRules() {
        $config = Zend_Registry::get('config');
        $templates = [];
        $out = [];
        $scaledPath = ltrim($config->cdn->path->upload->image . '/scaled/', '/');

        if (!empty($config->image->template)) {
            $templates = $config->image->template;
        }
        foreach ($templates as $tplName => $tplConfig) {
            $fallback = empty($tplConfig->fallback) ?
                'fallback_' . $tplName . '.jpg' : $tplConfig->fallback;
            $out[] = [
                'Condition' => [
                    'HttpErrorCodeReturnedEquals' => '404',
                    'KeyPrefixEquals' => $scaledPath . $tplName
                ],
                'Redirect' => [
                    'ReplaceKeyWith' => 'media/images/404fallbacks/' . $fallback
                ]
            ];
        }

        return $out;
    }
}
