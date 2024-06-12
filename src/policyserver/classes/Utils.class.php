<?php


class Utils
{

/**
     * Parse JID information to extrart RoomName and tenant from the conference JID
     *
     * @param string $JID   conference JID in format roomname@conference.[tenant].meeting_instance
     * @param array  $meeting_instances array of supported meeting instances.
     * @return array array containing the roomname and the tenant if it exist or only the roomname if tenant is empty.
     */
    private static function parseJIDinformations($JID,$meeting_instances){
        $curentInstance ="";
        foreach ($meeting_instances as $val ){
            error_log($val);
            if ( str_contains($JID,$val) ) {
                $curentInstance = $val;
                break;
            }
        }
        $patern = '/(.*)@conference\.?([^.]*)\.'.$curentInstance.'/';
        preg_match_all($patern, $JID, $roomElements,PREG_SET_ORDER);
        error_log(print_r($roomElements,true));
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
        $roomElements = Utils::parseJIDinformations($JID,$meeting_instances);
        return $roomElements[0][2];
    }

    /**
     * Extact roomName part from conference JID
     *
     * @param string $JID   conference JID in format roomname@conference.[tenant].meeting_instance
     * @param string $meeting_instance
     * @return string rromName from JID
     */
    public static function extractRoomName($JID,$meeting_instances){
        $roomElements = Utils::parseJIDinformations($JID,$meeting_instances);
        return $roomElements[0][1];
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
        if ( !empty($roomElements[1]) )
            $fullUrlRoomName = $roomElements[0][2]."/".$roomElements[0][1];
        else 
            $fullUrlRoomName = $roomElements[0][1];
        return $fullUrlRoomName;
    }
}