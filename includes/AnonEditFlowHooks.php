<?php
namespace Ark\AnonEditFlow;

use SkinTemplate;
use SpecialPage;
use MediaWiki\MediaWikiServices;
use LoginHelper;

class AnonEditFlowHooks {
    const MSG_CREATE_ACCOUNT_TO_EDIT = 'ark-edit-accountrequired';

    public static function onLoginFormValidErrorMessages( &$messages ) {
        $messages[] = self::MSG_CREATE_ACCOUNT_TO_EDIT;
    }

    public static function onSkinTemplateNavigation( SkinTemplate $skin, array &$links ) {
        global $wgNamespaceProtection;
        // Check if 'views' navigation is defined, and 'viewsource' is defined within; otherwise do not run
        if ( array_key_exists( 'views', $links ) && array_key_exists( 'viewsource', $links['views'] ) ) {
            // Require that the user is an anon
            if ( $skin->getAuthority()->getUser()->isAnon() ) {
                $title = $skin->getRelevantTitle();
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
                $injection = [
                    'edit' => [
                        'class' => false,
                        'text' => wfMessage( 'edit' )->setContext( $skin->getContext() )->text(),
                        'href' => SpecialPage::getTitleFor( 'CreateAccount' )->getLocalURL( [
                            'warning' => self::MSG_CREATE_ACCOUNT_TO_EDIT,
                            'returnto' => $title->getPrefixedDBKey(),
                            'returntoquery' => 'action=edit'
                        ] ),
                        'primary' => true
                    ]
                ];
                // Inject the new link onto second position
                $links['views'] = array_slice( $links['views'], 0, 1, true ) + $injection +
                    array_slice( $links['views'], 1, null, true );
            }
        }
    }

    private static function doUsersProbablyHaveTheseRights( /*string|array*/ $rights ) {
        if ( is_array( $rights ) )
            return empty( $rights ) || ( count( $rights ) === 1 && ( $rights[0] === 'autoconfirmed' || $rights[0] === '' ) );
        
        return $rights === '' || $rights === 'autoconfirmed';
    }
}
