<?php
/**
 * Scss plugin for Craft CMS 3.x
 *
 * Compiler for SCSS
 *
 * @link      https://chasegiunta.com
 * @copyright Copyright (c) 2018 Chase Giunta
 */
namespace chasegiunta\scss\services;

use Craft;
use chasegiunta\scss\Scss;
use craft\base\Component;
use ScssPhp\ScssPhp\Compiler;
use yii\base\Event;
use craft\web\View;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;
use MatthiasMullie\Minify;

/**
 * ScssService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Chase Giunta
 * @package   Scss
 * @since     1.0.0
 */
class ScssService extends Component
{
    public $scss = '';
    public $attributes = '';

    public function __construct() {
        Event::on(
            View::class,
            View::EVENT_AFTER_RENDER_TEMPLATE,
            function() {
                $this->afterTemplateRender();
            }
        );
    }

    // Public Methods
    // =========================================================================

    public function scss($scss = "", $attributes = "")
    {
        $this->scss .= $scss;
        $this->attributes .= $attributes;
    }

    public function afterTemplateRender() {
        $attributes = unserialize($this->attributes);
        $scssphp = new Compiler();
        $devMode = ( getEnv('ENVIRONMENT') === 'dev' ? true : false );

        if (Craft::$app->getConfig()->general->devMode) {
            $outputFormat = Scss::$plugin->getSettings()->devModeOutputFormat;
        } else {
            $outputFormat = Scss::$plugin->getSettings()->outputFormat;
        }

        if ($attributes['expanded']) {
            $outputFormat = 'Expanded';
        }
        if ($attributes['crunched']) {
            $outputFormat = 'Crunched';
        }
        if ($attributes['compressed']) {
            $outputFormat = 'Compressed';
        }
        if ($attributes['compact']) {
            $outputFormat = 'Compact';
        }
        if ($attributes['nested']) {
            $outputFormat = 'Nested';
        }

        $scssphp->setFormatter("ScssPhp\ScssPhp\Formatter\\$outputFormat");

        $rootPath = Craft::getAlias('@root');
        $scssphp->setImportPaths($rootPath);

        if ($attributes['debug'] || Scss::$plugin->getSettings()->debug) {
            $scssphp->setLineNumberStyle(Compiler::LINE_COMMENTS);
        }

        $compiled = $scssphp->compile($this->scss);
        $autoprefixer = new Autoprefixer($compiled);
        $prefixed = $autoprefixer->compile();
        if (! $devMode ) {
            $minifier = new Minify\CSS($prefixed);
            $minified = $minifier->minify();
            Craft::$app->view->registerCss($minified);
        } else {
            Craft::$app->view->registerCss($prefixed);
        }
    }
}
