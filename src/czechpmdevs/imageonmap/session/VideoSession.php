<?php
namespace czechpmdevs\imageonmap\session;
use czechpmdevs\imageonmap\item\CustomItemRegistry;
use czechpmdevs\imageonmap\item\FilledMap;
use czechpmdevs\imageonmap\Main as MapVideo;
use czechpmdevs\imageonmap\task\VideoPlayTask;
use czechpmdevs\imageonmap\video\Video;
use czechpmdevs\imageonmap\video\VideoPlaySettings;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use WeakMap;


/**
 * Class VideoSession
 * @package Jibix\MapVideo\session
 * @author Jibix
 * @date 05.12.2023 - 23:04
 * @project MapVideo
 */
final class VideoSession{

    private static WeakMap $sessions;

    public static function get(Player $player): self{
        self::$sessions ??= new WeakMap();
        return self::$sessions[$player] ??= new VideoSession($player);
    }

    private ?Video $video = null;

    private function __construct(private Player $player){}

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getVideo(): ?Video{
        return $this->video;
    }

    public function play(Video $video, VideoPlaySettings $settings): void{
        $this->stop();
        $this->video = $video;
        MapVideo::getPlugin()->getScheduler()->scheduleRepeatingTask(new VideoPlayTask($this, $settings), 1);
        $item = CustomItemRegistry::FILLED_MAP()->setMapId($video->getId());
        if ($settings->playInOffHand()) {
            $this->player->getOffHandInventory()->setItem(0, $item);
        } else {
            $this->player->getInventory()->setItemInHand($item);
        }
    }

    public function stop(): void{
        //Cleaning map images
        $blankMap = CustomItemRegistry::FILLED_MAP()->setMapId(FilledMap::BLANK_MAP_ID);
        $stateId = $blankMap->getStateId();
        /** @var Inventory $inventory */
        foreach ([$this->player->getInventory(), $this->player->getOffHandInventory()] as $inventory) {
            foreach ($inventory->getContents() as $slot => $item) {
                if ($item->getStateId() === $stateId) $inventory->setItem($slot, $blankMap);
            }
        }
        $this->video = null;
    }
}
