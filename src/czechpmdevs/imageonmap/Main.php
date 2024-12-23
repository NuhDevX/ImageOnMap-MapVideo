<?php

/**
 * ImageOnMap - Easy to use PocketMine plugin, which allows loading images on maps
 * Copyright (C) 2021 - 2023 CzechPMDevs
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace czechpmdevs\imageonmap;

use czechpmdevs\imageonmap\command\ImageCommand;
use czechpmdevs\imageonmap\command\VideoCommand;
use czechpmdevs\imageonmap\image\BlankImage;
use czechpmdevs\imageonmap\utils\PermissionDeniedException;
use czechpmdevs\imageonmap\utils\CustomMapItemDataPacket;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\StringToItemParser;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\network\mcpe\protocol\PacketPool;
use function array_key_exists;
use function extension_loaded;
use function mkdir;

class Main extends PluginBase implements Listener {
	use DataProviderTrait;

	private static ImageOnMap $instance;
  
	public function onEnable(): void {
		self::$instance = $this;

		if(!extension_loaded("gd")) {
			throw new DisablePluginException("GD extension is required in order to run ImageOnMap plugin.");
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

    $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
		@mkdir($this->getDataFolder() . "data");
		@mkdir($this->getDataFolder() . "images");
    
                PacketPool::getInstance()->registerPacket(new CustomMapItemDataPacket());

		try {
			$this->loadCachedMaps($this->getDataFolder() . "data");
		} catch(PermissionDeniedException) {
			$this->getLogger()->error("Could not load cached maps - Target file could not be accessed.");
		}

		$this->getServer()->getCommandMap()->register("imageonmap", new ImageCommand());
		$this->getServer()->getCommandMap()->register("video", new VideoCommand());

		$this->registerItem();
		$this->getServer()->getAsyncPool()->addWorkerStartHook(function(int $worker): void {
			$this->getServer()->getAsyncPool()->submitTaskToWorker(new class extends AsyncTask {
				public function onRun(): void {
					ImageOnMap::registerItem();
				}
			}, $worker);
		});
	}

	protected function onDisable(): void {
		try {
			$this->saveCachedMaps($this->getDataFolder() . "data");
		} catch(PermissionDeniedException) {
			$this->getLogger()->error("Could not save cached maps - Target file could not be accessed.");
		}
	}

	/**
	 * @internal
	 *
	 * @deprecated Internal
	 */
	public static function registerItem(): void {
		$item = FilledMapItemRegistry::FILLED_MAP();

		GlobalItemDataHandlers::getDeserializer()->map(ItemTypeNames::FILLED_MAP, fn() => clone $item);
		GlobalItemDataHandlers::getSerializer()->map($item, fn() => new SavedItemData(ItemTypeNames::FILLED_MAP));

		StringToItemParser::getInstance()->register("filled_map", fn() => clone $item);
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
           $packet = $event->getPacket();

           if (!$packet instanceof MapInfoRequestPacket) {
           return;
           }

          $origin = $event->getOrigin();

          if ($origin->getPlayer() !== null && $packet->mapId == FilledMap::BLANK_MAP_ID) {
          $origin->sendDataPacket(Utils::getBlankImagePacket());
          return;
           }
     
           if (!array_key_exists($packet->mapId, $this->cachedMaps)) {
           $origin->sendDataPacket(BlankImage::get()->getPacket($packet->mapId));
           $this->getLogger()->debug("Unknown map id $packet->mapId received from {$origin->getDisplayName()}");
           return;
           }

         $origin->sendDataPacket($this->getCachedMap($packet->mapId)->getPacket($packet->mapId));
        }


	/**
	 * @internal
	 */
	public static function getInstance(): ImageOnMap {
		return self::$instance;
	}
}
