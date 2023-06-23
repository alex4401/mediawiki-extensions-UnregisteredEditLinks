<?php
namespace MediaWiki\Extension\UnregisteredEditLinks;

use SkinTemplate;
use SpecialPage;
use MediaWiki\MediaWikiServices;
use Title;

class Hooks implements
    \MediaWiki\Hook\SkinTemplateNavigation__UniversalHook,
    \MediaWiki\Hook\LoginFormValidErrorMessagesHook
{
    const MSG_CREATE_ACCOUNT_TO_EDIT = 'accountrequiredtoedit';

    public function onLoginFormValidErrorMessages( array &$messages ) {
        $messages[] = self::MSG_CREATE_ACCOUNT_TO_EDIT;
    }

    public function onSkinTemplateNavigation__Universal( $skin, &$links ): void {
        global $wgNamespaceProtection;
        // Check if 'views' navigation is defined, and 'viewsource' is defined within; otherwise do not run
        if ( isset( $links['views'] ) ) {
            $title = $skin->getRelevantTitle();

            $shouldModify = self::checkCriteria( $title, $links );

            if ( !$shouldModify ) {
                return;
            }

            // Require that the user is an anon
            if ( $skin->getAuthority()->getUser()->isAnon() ) {
                $nsIndex = $title->getNamespace();
                // Check namespace restrictions
                if ( isset( $wgNamespaceProtection[ $nsIndex ] )
                    && !self::doUsersProbablyHaveTheseRights( $wgNamespaceProtection[ $nsIndex ] ) ) {
                    return;
                }
                // Check page restrictions
                $restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();
                if ( !self::doUsersProbablyHaveTheseRights( $restrictionStore->getRestrictions( $title, 'edit' ) ) ) {
                    return;
                }

                // Prepare the action link
                $injection = self::getActionLink( $skin, $title );
                // Inject the new link onto second position
                $links['views'] = array_slice( $links['views'], 0, 1, true ) + $injection +
                    array_slice( $links['views'], 1, null, true );
            }
        }
    }

    private static function checkCriteria( Title $title, array &$links ): bool {
        if ( isset( $links['views']['viewsource'] ) && !isset( $links['views']['edit'] ) ) {
            return true;
        }

        $contentNs = $GLOBALS['wgAEFAdvertiseCreationInContentNs'];
        $canExist = $GLOBALS['wgAEFAdvertiseCreationIfCanExist'];
        if ( ( $contentNs || $canExist ) && !$title->exists()
            && ( ( $canExist && $title->canExist() ) || ( $contentNs && $title->isContentPage() ) )
        ) {
            return true;
        }

        return false;
    }

    private static function getActionLink( SkinTemplate $skin, Title $title ): array {
        return [
            'edit' => [
                'class' => false,
                'text' => wfMessage( $title->exists() ? 'unregistered-edit' : 'unregistered-create' )->setContext( $skin->getContext() )->text(),
                'href' => SpecialPage::getTitleFor( 'CreateAccount' )->getLocalURL( [
                    'warning' => self::MSG_CREATE_ACCOUNT_TO_EDIT,
                    'returnto' => $title->getPrefixedDBKey(),
                    'returntoquery' => 'action=edit'
                ] ),
                'primary' => true
            ]
        ];
    }

    private static function doUsersProbablyHaveTheseRights( /*string|array*/ $rights ) {
        if ( is_array( $rights ) )
            return empty( $rights ) || ( count( $rights ) === 1 && ( $rights[0] === 'autoconfirmed' || $rights[0] === '' ) );
        
        return $rights === '' || $rights === 'autoconfirmed';
    }
}
