<?php
declare(strict_types=1);

namespace Smartex\PageBuilderRequireJsFix\Block;

use Magento\Framework\Filesystem;
use Magento\Framework\App\State;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\RequireJs\Config as RequireJsConfig;
use Magento\Framework\View\Asset\ConfigInterface;
use Magento\Framework\View\Asset\Minification;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Page\Config as PageConfig;

class Config extends AbstractBlock
{
    /**
     * @var PageConfig
     */
    private $pageConfig;

    /**
     * @var RequireJsConfig
     */
    private $config;

    /**
     * @var Minification
     */
    private $minification;

    /**
     * @var ConfigInterface
     */
    private $bundleConfig;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var State
     */
    private $state;

    /**
     * @param Context $context
     * @param PageConfig $pageConfig
     * @param RequireJsConfig $config
     * @param Minification $minification
     * @param ConfigInterface $bundleConfig
     * @param Filesystem $filesystem
     * @param State $state
     */
    public function __construct(
        Context $context,
        PageConfig $pageConfig,
        RequireJsConfig $config,
        Minification $minification,
        ConfigInterface $bundleConfig,
        Filesystem $filesystem,
        State $state,
        array $data = []
    ) {
        $this->pageConfig = $pageConfig;
        $this->config = $config;
        $this->minification = $minification;
        $this->bundleConfig = $bundleConfig;
        $this->filesystem = $filesystem;
        $this->state = $state;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        $this->fixConfig();

        return parent::_prepareLayout();
    }

    /**
     * Remove 'integrity' and 'crossorigin' attributes from 'requirejs-config' asset.
     * 
     * @return void
     */
    private function fixConfig(): void
    {
        $assetCollection = $this->pageConfig->getAssetCollection();
        $allAssets = $assetCollection->getAll();

        $rjsFile = RequireJsConfig::REQUIRE_JS_FILE_NAME;
        $rjsConfigFile = $this->config->getConfigFileRelativePath();

        $rjsAsset = $allAssets[$rjsFile] ?? null;
        $rjsConfigAsset = $allAssets[$rjsConfigFile] ?? null;

        if ($rjsAsset && $rjsConfigAsset) {
            $after = $this->getAfterKey();

            foreach ($assetCollection->getGroups() as $group) {
                if ($group->has($rjsFile) && !$group->has($rjsConfigFile)) {
                    $group->insert(
                        $rjsConfigFile,
                        $rjsConfigAsset,
                        $after
                    );
                }
                if (!$group->has($rjsFile) && $group->has($rjsConfigFile)) {
                    $group->remove($rjsConfigFile);
                }
            }
        }
    }

    /**
     * Get asset key to insert requirejs-config after.
     * Performs same checks as \Magento\RequireJs\Block\Html\Head\Config to ensure
     * asset is inserted at correct place.
     * 
     * @return string
     */
    private function getAfterKey(): string
    {
        $after = RequireJsConfig::REQUIRE_JS_FILE_NAME;
        if ($this->minification->isEnabled('js')) {
            $after = $this->config->getMinResolverRelativePath();
        }

        if ($this->checkIfExists($this->config->getMapFileRelativePath())) {
            $after = $this->config->getMapFileRelativePath();
        }

        if ($this->bundleConfig->isBundlingJsFiles()
            && $this->state->getMode() == State::MODE_PRODUCTION
        ) {
            $after = RequireJsConfig::STATIC_FILE_NAME;
        }

        return $after;
    }

    /**
     * @param string $relPath
     * @return bool
     */
    private function checkIfExists(string $relPath): bool
    {
        return $this->filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW)
            ->isExist($relPath);
    }
}
