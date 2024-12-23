<?php
namespace czechpmdevs\imageonmap\task;
use czechpmdevs\imageonmap\item\FilledMap;
use czechpmdevs\imageonmap\session\VideoSession;
use czechpmdevs\imageonmap\video\VideoPlaySettings;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;


/**
 * Class VideoPlayTask
 * @package Jibix\MapVideo\task
 * @author Jibix
 * @date 01.12.2023 - 15:04
 * @project MapVideo
 */
class VideoPlayTask extends Task{

    private int $frame = 0;
    private int $videoId;

    public function __construct(private VideoSession $session, private VideoPlaySettings $settings){
        $this->videoId = $this->session->getVideo()->getId();
    }

    public function onRun(): void{
        $video = $this->session->getVideo();
        if ($video === null || $video->getId() !== $this->videoId || !$this->session->getPlayer()->isOnline()) {
            $this->getHandler()?->cancel();
            return;
        }
        if ($this->getMapItem()->getNamedTag()->getLong(FilledMap::MAP_ID_TAG, -1) !== $this->videoId) return;
        $packet = $video->getFrame($this->frame++);
        if ($packet === null) {
            if ($this->settings->playOnRepeat()) {
                $this->frame = 0;
            } else {
                $this->session->end();
                $this->getHandler()?->cancel();
            }
            return;
        }
        $this->session->getPlayer()->getNetworkSession()->sendDataPacket($packet);
    }

    private function getMapItem(): Item{
        if ($this->settings->playInOffHand()) {
            return $this->session->getPlayer()->getOffHandInventory()->getItem(0);
        } else {
            return $this->session->getPlayer()->getInventory()->getItemInHand();
        }
    }
}
