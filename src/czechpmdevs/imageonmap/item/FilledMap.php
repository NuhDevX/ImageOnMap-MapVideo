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

namespace czechpmdevs\imageonmap\item;

use czechpmdevs\imageonmap\FilledMapItemRegistry;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

class FilledMap extends Item {
	private int $uuid;

	public function setMapId(int $uuid): self {
		$this->uuid = $uuid;
		return $this;
	}

	public function getMapId(): int{
                return $this->uuid;
	}

	protected function serializeCompoundTag(CompoundTag $tag): void {
		parent::serializeCompoundTag($tag);
		$tag->setLong("map_uuid", $this->uuid);
	}

	protected function deserializeCompoundTag(CompoundTag $tag): void {
		parent::deserializeCompoundTag($tag);
		$this->uuid = $tag->getLong("map_uuid");
	}

	/**
	 * @deprecated
	 * @see FilledMapItemRegistry::FILLED_MAP()
	 */
	public static function get(): FilledMap {
		return FilledMapItemRegistry::FILLED_MAP();
	}
}
