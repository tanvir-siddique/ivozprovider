<?php

namespace Dialplan;

use Agi\Action\ExtensionAction;
use Agi\Action\ExternalUserCallAction;
use Agi\Action\FriendCallAction;
use Agi\Action\ServiceAction;
use Agi\ChannelInfo;
use Agi\Wrapper;
use Helpers\EndpointResolver;
use Ivoz\Provider\Domain\Model\CompanyService\CompanyServiceInterface;
use RouteHandlerAbstract;


class Users extends RouteHandlerAbstract
{
    /**
     * @var Wrapper
     */
    protected $agi;

    /**
     * @var ChannelInfo
     */
    protected $channelInfo;

    /**
     * @var EndpointResolver
     */
    protected $endpointResolver;

    /**
     * @var ServiceAction
     */
    protected $serviceAction;

    /**
     * @var ExtensionAction
     */
    protected $extensionAction;

    /**
     * @var FriendCallAction
     */
    protected $friendCallAction;

    /**
     * @var ExternalUserCallAction
     */
    protected $externalUserCallAction;

    /**
     * Users constructor.
     *
     * @param Wrapper $agi
     * @param ChannelInfo $channelInfo
     * @param EndpointResolver $endpointResolver
     * @param ServiceAction $serviceAction
     * @param ExtensionAction $extensionAction
     * @param FriendCallAction $friendCallAction
     * @param ExternalUserCallAction $externalUserCallAction
     */
    public function __construct(
        Wrapper $agi,
        ChannelInfo $channelInfo,
        EndpointResolver $endpointResolver,
        ServiceAction $serviceAction,
        ExtensionAction $extensionAction,
        FriendCallAction $friendCallAction,
        ExternalUserCallAction $externalUserCallAction
    )
    {
        $this->agi = $agi;
        $this->channelInfo = $channelInfo;
        $this->endpointResolver = $endpointResolver;
        $this->serviceAction = $serviceAction;
        $this->extensionAction = $extensionAction;
        $this->friendCallAction = $friendCallAction;
        $this->externalUserCallAction = $externalUserCallAction;
    }

    /**
     * Outgoing calls from terminals to Extensions, Services or World
     *
     * @throws \Assert\AssertionFailedException
     */
    public function process()
    {
        /**
         * Determine who is placing this call:
         * - SIPTRANSFER: set by asterisk on blind transfers
         * - Diversion:   set by asterisk on 302 Moved SIP Message
         * - Endpoint:    set by asterisk on matching endpoint
         */

        /**
         * Blind Transfer from User's terminal
         */
        if ($this->agi->getVariable("SIPTRANSFER")) {
            // Asterisk stores the Refered-By header of the transferer
            $transfererHdr = $this->agi->getVariable("SIPREFERREDBYHDR");

            $transfererURI = $this->agi->extractURI($transfererHdr, "uri");
            $transfererNum = $this->agi->extractURI($transfererHdr, "num");
            $transfererDomain = $this->agi->extractURI($transfererHdr, "domain");

            $endpointName = $this->endpointResolver->getEndpointNameFromContact($transfererNum, $transfererDomain);

            // Transferer is the new caller
            $user = $this->endpointResolver->getUserFromEndpoint($endpointName);
            $this->channelInfo->setChannelCaller($user);

            // Set Caller extension in Referred header
            $transfererURI = str_replace($endpointName, $user->getExtensionNumber(), $transfererURI);
            $this->agi->setVariable("__SIPREFERREDBYHDR", $transfererURI);

            /**
             * Redirection from User's terminal - 302 Moved
             */
        } else if ($forwarder = $this->agi->getRedirecting('from-num')) {
            // 302 Moved here caller. The variable MUST store the last dialed endpoint
            $endpointName = $this->agi->getVariable("DIAL_ENDPOINT");

            // Forwarder is the new caller
            $user = $this->endpointResolver->getUserFromEndpoint($endpointName);
            $this->channelInfo->setChannelCaller($user);

            // Remove any Diversion header generated by Terminals (they will be added if required later)
            $this->agi->setRedirecting('count', 0);

            /**
             * Normal call from User's terminal
             */
        } else {
            // Get endpoint name from channel
            $endpointName = $this->agi->getEndpoint();

            $user = $this->endpointResolver->getUserFromEndpoint($endpointName);
            $this->channelInfo->setChannelCaller($user);
            $this->channelInfo->setChannelOrigin($user);
        }

        // Set Company/Brand/Generic Music class
        $company = $user->getCompany();
        $this->agi->setVariable("__COMPANYID", $company->getId());

        // Mark this call as generated from user
        $this->agi->setVariable("__CALL_TYPE", "internal");

        // Set Outgoing Channels X-CID header variable
        $this->agi->setVariable("__CALL_ID", $this->agi->getCallId());

        // Set user language and music
        $this->agi->setVariable("CHANNEL(language)", $user->getLanguageCode());
        $this->agi->setVariable("CHANNEL(musicclass)", $company->getMusicClass());

        // Check company On demand record code
        if ($company->getOnDemandRecord()) {
            $this->agi->setVariable("FEATUREMAP(automixmon)", $company->getOnDemandRecordDTMFs());
        }

        // Remove any Diversion header generated by Terminals (they will be added if required later)
        if ($this->agi->getRedirecting('count')) {
            $this->agi->setRedirecting('count', 0);
        }

        // Check User's permission to does this call
        $exten = $this->agi->getExtension();

        // Check if this extension starts with '*' code
        if (strpos($exten, '*') === 0) {
            if ($exten == $this->serviceAction::HELLOCODE) {
                // Handle service code
                $this->serviceAction
                    ->processHello();
                return;
            }

            /** @var CompanyServiceInterface $service */
            if (($service = $company->getService($exten))) {

                // Handle service code
                $this->serviceAction
                    ->setService($service)
                    ->process();

            } else {
                // Decline this call if not matching service is found
                $this->agi->error("Invalid Service code %s for comany %s", $exten, $company);
                $this->agi->hangup();
            }

            // Check if this is an extension call
        } elseif (($dstExtension = $company->getExtension($exten))) {

            // Update Diversion Header with User Extension Number
            if (isset($forwarder) && !empty($forwarder)) {
                $this->agi->setRedirecting('from-num,i', $user->getExtensionNumber());
                $this->agi->setRedirecting('from-tag,i', $user->getExtensionNumber());
                $this->agi->setRedirecting('count,i', 1);
            }

            // Handle extension
            $this->extensionAction
                ->setExtension($dstExtension)
                ->process();

            // Check if this number matches one of friendly trunks patterns
        } else if (($friend = $company->getFriend($exten))) {

            // Update Diversion Header with User Extension Number
            if (isset($forwarder) && !empty($forwarder)) {
                $this->agi->setRedirecting('from-num,i', $user->getExtensionNumber());
                $this->agi->setRedirecting('from-tag,i', $user->getExtensionNumber());
                $this->agi->setRedirecting('count,i', 1);
            }

            // Handle call through friendly trunk
            $this->friendCallAction
                ->setFriend($friend)
                ->setDestination($exten)
                ->process();

            // This number don't belong to IvozProvider
        } else {

            // Update Diversion Header with User Outgoing DDI
            if (isset($forwarder) && !empty($forwarder)) {
                $this->agi->setRedirecting('from-name,i', $user->getFullName());
                $this->agi->setRedirecting('from-num,i',  $user->getOutgoingDDINumber());
                $this->agi->setRedirecting('from-tag,i',  $user->getExtensionNumber());
                $this->agi->setRedirecting('count,i', 1);
            }

            // Otherwise, handle this call as external
            $this->externalUserCallAction
                ->setDestination($exten)
                ->process();
        }
    }

}