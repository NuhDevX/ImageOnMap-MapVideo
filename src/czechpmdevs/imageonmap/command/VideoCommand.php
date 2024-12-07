<?php
namespace czechpmdevs\imageonmap\command;
use czechpmdevs\imageonmap\session\VideoSession;
use czechpmdevs\imageonmap\video\Video;
use czechpmdevs\imageonmap\video\VideoManager;
use czechpmdevs\imageonmap\video\VideoPlaySettings;
use czechpmdevs\imageonmap\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;


/**
 * Class VideoCommand
 * @package Jibix\MapVideoExample\command
 * @author Jibix
 * @date 06.12.2023 - 19:06
 * @project MapVideo
 */
class VideoCommand extends Command implements PluginOwned{

    //I hate you poggit
    public function getOwningPlugin(): Plugin{
        return Main::getInstance();
    }

    public function __construct(){
        parent::__construct("video", "Play a video on a map", "/$name <name>");
        $this->setPermission("video.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cThis command must be executed as a player!");
            return;
        }
        if (!$args) throw new InvalidCommandSyntaxException();
        $video = array_shift($args);
        if (!is_file($file = Main::getInstance()->getDataFolder() . "$video.gif")) {
            $sender->sendMessage("§cThis video could not be found!");
            return;
        }
        $name = $sender->getName();
        VideoManager::getInstance()->loadVideo(
            Video::id($video),
            $file,
            static function (Video $video) use ($name): void{
                if (!$player = Server::getInstance()->getPlayerExact($name)) return;
                $player->sendActionBarMessage("§aDone, starting video...");
                VideoSession::get($player)->play($video, new VideoPlaySettings());
            },
            static function (int $totalFrames, int $loadedFrames) use ($name): void{
                $player = Server::getInstance()->getPlayerExact($name);
                $player?->sendActionBarMessage("§bLoaded frame §a{$loadedFrames}§7/§c{$totalFrames} §7(§6" . round($loadedFrames / $totalFrames * 100) . "%§7)");
            }
        );
    }
}
