<?php

use function PHPUnit\Framework\isEmpty;

class Utils
{

/**
     * Parse JID information to extrart RoomName and tenant from the conference JID
     *
     * @param string $JID   conference JID in format roomname@conference.[tenant].meeting_instance
     * @param array  $meeting_instances array of supported meeting instances.
     * @return array array containing the roomname and the tenant if it exist or only the roomname if tenant is empty.
     */
    public static function parseJIDinformations($JID,$meeting_instances){
        $curentInstance ="";
        foreach ($meeting_instances as $val ){
            if ( str_contains($JID,$val) ) {
                $curentInstance = $val;
                break;
            }
        }
        $patern = '/(.*)@conference\.?([^.]*)\.'.$curentInstance.'/';
        preg_match_all($patern, $JID, $roomElements,PREG_SET_ORDER);
        return $roomElements;
    }

    public static function isValidDomain($domain,$meeting_instances){
        foreach ($meeting_instances as $value){
            if ( str_contains($domain,$value) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extact tenant part from conference JID
     *
     * @param string $JID   conference JID in format roomname@conference.[tenant].meeting_instance
     * @param array  $meeting_instances
     * @return string tenant value, empty if there is no tenant in the provided JID.
     */
    public static function extractTenant($JID,$meeting_instances){
        $tenant = NULL;
        $roomElements = Utils::parseJIDinformations($JID,$meeting_instances);
        if ( $roomElements[0][2] == NULL ) {
            // Look at tenant in roomname part 
            $roomName = $roomElements[0][1];
            $roomNameElement = explode("/",$roomName);
            if (sizeof($roomNameElement)==2)
                $tenant = $roomNameElement[0];
        }
        else 
            $tenant = $roomElements[0][2];
        return $tenant;
    }

    /**
     * Extact roomName part from conference JID
     *
     * @param string $JID   conference JID in format roomname@conference.[tenant].meeting_instance
     * @param array $meeting_instances array of valid instance
     * @return string rromName from JID
     */
    public static function extractRoomName($JID,$meeting_instances){
        $roomElements = Utils::parseJIDinformations($JID,$meeting_instances);
        return $roomElements[0][1];
    }

    /**
     * Extact roomName from request with or without tenant prefix
     *
     * @param string room request
     * @return string romName 
     */
    public static function getRoomName($roomRequest){
        $roomNameElement = explode("/",$roomRequest);
        if (sizeof($roomNameElement)==2)
            $roomName = $roomNameElement[1];
        else 
            $roomName = $roomNameElement[0];
        return $roomName;
    }


   /**
     * Extact roomName from request with or without tenant prefix
     *
     * @param string room request
     * @return string romName 
     */
    public static function getTenantFromRoom($roomRequest){
        $roomNameElement = explode("/",$roomRequest);
        if (sizeof($roomNameElement)==2)
            $tenant = $roomNameElement[0];
        else 
            $tenant = "";
        return $tenant;
    }

    /**
     * Extact meeting_instances part from conference JID
     *
     * @param string $domain   conference doamin in format conference.[tenant].meeting_instance
     * @param array $meeting_instances array of valid instance
     * @return string rromName from JID
     */
    public static function extractMeetingInstance($domain,$meeting_instances){
        $curentInstance ="";
        foreach ($meeting_instances as $val ){
            if ( str_contains($domain,$val) ) {
                $curentInstance = $val;
                break;
            }
        }
        return $curentInstance;
    }

    /**
     * Format a full url roomName by extracting the tenant and the roomName from the JID
     *
     * @param string $JID   conference JID in format roomname@conference.[tenant].meeting_instance
     * @param string $meeting_instance
     * @return string tenat/rooname value or roomane if tenant is empty
     */
    public static function extractFullUrlRoomName($JID,$meeting_instances){
        $roomElements = Utils::parseJIDinformations($JID,$meeting_instances);
        if ( !empty($roomElements[0][2]) )
            $fullUrlRoomName = $roomElements[0][2]."/".$roomElements[0][1];
        else 
            $fullUrlRoomName = $roomElements[0][1];
        return $fullUrlRoomName;
    }

    /**
     * Format a full url roomName by extracting the tenant and the roomName from the JID
     *
     * @param string $JID   conference JID in format roomname@conference.[tenant].meeting_instance
     * @param string $meeting_instance
     * @return string tenat/rooname value or roomane if tenant is empty
     */
    public static function formatIntoJid($roomName,$meeting_instance){
        $tenant = Utils::getTenantFromRoom($roomName);
        if ($tenant != "")
            $tenant = '.'.$tenant;
        $roomNameWithoutTenant = Utils::getRoomName($roomName);
        return "$roomNameWithoutTenant@conference$tenant.$meeting_instance";
    }

}