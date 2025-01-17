<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Filelist\Controller\File;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Display forms for creating folders (1 to 10).
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class CreateFolderController
{
    protected int $folderNumber = 10;
    protected int $number = 1;

    /**
     * Set with the target path inputted in &target
     */
    protected string $target = '';

    /**
     * The folder object which is the target directory
     */
    protected ?Folder $folderObject = null;

    /**
     * Return URL of file list module.
     */
    protected string $returnUrl = '';

    protected ModuleTemplate $view;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->view = $this->moduleTemplateFactory->create($request);
        $this->initialize($request);
        $hasPermissions = $this->folderObject->checkActionPermission('add');
        $assigns = [
            'hasPermissions' => $hasPermissions,
            'target' => $this->target,
            'confirmTitle' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:pleaseConfirm'),
            'confirmText' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.redraw'),
            'selfUrl' => (string)$this->uriBuilder->buildUriFromRoute('file_newfolder', [
                'target' => $this->target,
                'returnUrl' => $this->returnUrl,
                'number' => 'AMOUNT',
            ]),
            'returnUrl' => $this->returnUrl,
        ];

        // Making the selector box for the number of concurrent folder-creations
        $this->number = MathUtility::forceIntegerInRange($this->number, 1, 10);
        for ($a = 1; $a <= $this->folderNumber; $a++) {
            $options = [];
            $options['value'] = $a;
            $options['selected'] = ($this->number == $a ? ' selected="selected"' : '');
            $assigns['options'][] = $options;
        }
        // Making the number of new-folder boxes needed:
        for ($a = 0; $a < $this->number; $a++) {
            $folder = [];
            $folder['this'] = $a;
            $folder['next'] = $a + 1;
            $assigns['folders'][] = $folder;
        }

        $this->view->assignMultiple($assigns);
        return $this->view->renderResponse('File/CreateFolder');
    }

    protected function initialize(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->number = (int)($parsedBody['number'] ?? $queryParams['number'] ?? 1);
        $this->target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        // create the folder object
        if ($this->target) {
            $this->folderObject = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->target);
        }
        // Cleaning and checking target directory
        if (!$this->folderObject instanceof Folder) {
            $title = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:paramError');
            $message = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:targetNoDir');
            throw new \RuntimeException($title . ': ' . $message, 1294586845);
        }
        if ($this->folderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFolderAccessPermissionsException(
                'You are not allowed to access folders outside your storages',
                1375889838
            );
        }

        $this->view->getDocHeaderComponent()->setMetaInformationForResource($this->folderObject);

        if ($this->returnUrl) {
            $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }

        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-menu.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/create-folder.js');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
