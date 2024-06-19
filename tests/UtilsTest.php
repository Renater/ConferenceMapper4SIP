<?php

use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase {

    /**
     * Test isValidDomain
     *
     * @return void
     */
    public function testIsValidDomain() {
            $domain = "instance2";
            $domainBad = "instanceBAD";
            $meeting_instances = [
                "instance1",
                "instance2",
                "instance3"
            ];
            $result = Utils::isValidDomain($domain,$meeting_instances);
            $this->assertEquals(true, $result);
            $result = Utils::isValidDomain($domainBad,$meeting_instances);
            $this->assertEquals(false, $result);
    }

    /**
     * Test ExtractTenant
     *
     * @return void
     */
    public function testExtractTenant() {
        $JID = "bob45@conference.lobby.instance1";
        $JID2 = "loby/bob45@conference.instance1";
        $meeting_instances = [
            "instance1",
            "instance2",
            "instance3"
        ];
        $result = Utils::extractTenant($JID,$meeting_instances);
        $this->assertEquals("lobby", $result);
        $result = Utils::extractTenant($JID2,$meeting_instances);
        $this->assertEquals("loby", $result);
    }

    /**
     * Test isValidDomain
     *
     * @return void
     */
    public function testExtractMeetingInstance() {
        $JID = "loby/bob45@conference.lobby.instance2";
        $JID2 = "loby/bob45@conference.instance2";
        $meeting_instances = [
            "instance1",
            "instance2",
            "instance3"
        ];
        $result = Utils::extractMeetingInstance($JID,$meeting_instances);
        $this->assertEquals("instance2", $result);
        $result = Utils::extractMeetingInstance($JID2,$meeting_instances);
        $this->assertEquals("instance2", $result);
    }

    /**
     * Test parseJIDinformations
     *
     * @return void
     */
    public function testParseJidInformations() {
        $JID = "loby/bob45@conference.lobby.instance2";
        $meeting_instances = [
            "instance1",
            "instance2",
            "instance3"
        ];
        $result = Utils::parseJIDinformations($JID,$meeting_instances);
        $element = [
            "loby/bob45@conference.lobby.instance2",
            "loby/bob45",
            "lobby"
        ];
        $elementRef = [$element];
        $this->assertEquals($elementRef, $result);

        $JID2 = "bob45@conference.lobby.instance2";
        $result = Utils::parseJIDinformations($JID2,$meeting_instances);
        $element = [
            "bob45@conference.lobby.instance2",
            "bob45",
            "lobby"
        ];
        $elementRef = [$element];
        $this->assertEquals($elementRef, $result);

        $JID3 = "lobby/bob45@conference.instance3";
        $result = Utils::parseJIDinformations($JID3,$meeting_instances);
        $element = [
            "lobby/bob45@conference.instance3",
            "lobby/bob45",
            ""
        ];
        $elementRef = [$element];
        $this->assertEquals($elementRef, $result);

    }

    /**
     * Test getRoomName
     *
     * @return void
     */
    public function testGetRoomName() {
        $roomReq = "bob45";
        $roomReq2 = "lobby/bob45";
        $result = Utils::getRoomName($roomReq);
        $this->assertEquals("bob45", $result);
        $result = Utils::getRoomName($roomReq2);
        $this->assertEquals("bob45", $result);
    }


    /**
     * Test getRoomName
     *
     * @return void
     */
    public function testTenantFromRoom() {
        $roomReq = "bob45";
        $roomReq2 = "lobby/bob45";
        $result = Utils::getTenantFromRoom($roomReq);
        $this->assertEquals("", $result);
        $result = Utils::getTenantFromRoom($roomReq2);
        $this->assertEquals("lobby", $result);
    }

    /**
     * Test extractFullUrlRoomName
     *
     * @return void
     */
    public function testExtractFullUrlRoomName() {
        $JID = "bob45@conference.lobby.instance2";
        $JID2 = "bob45@conference.instance2";
        $JID3 = "baba/bob45@conference.instance2";

        $meeting_instances = [
            "instance1",
            "instance2",
            "instance3"
        ];
        $result = Utils::extractFullUrlRoomName($JID,$meeting_instances);
        $this->assertEquals("lobby/bob45", $result);
        $result = Utils::extractFullUrlRoomName($JID2,$meeting_instances);
        $this->assertEquals("bob45", $result);
        $result = Utils::extractFullUrlRoomName($JID3,$meeting_instances);
        $this->assertEquals("baba/bob45", $result);
    }

    /**
     * Test formatIntoJid
     *
     * @return void
     */
    public function testFormatIntoJid() {

        $roomName = "bob45";
        $roomName2 = "lobby/bob45";
        $meeting_instance = "instance2";

        $result = Utils::formatIntoJid($roomName,$meeting_instance);
        $this->assertEquals("bob45@conference.instance2", $result);
        $result = Utils::formatIntoJid($roomName2,$meeting_instance);
        $this->assertEquals("bob45@conference.lobby.instance2", $result);
    }

}