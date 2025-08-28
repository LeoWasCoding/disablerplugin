<?php

namespace LeoWasCoding\disableenchant;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\object\Painting;
use pocketmine\block\BlockTypeIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Main extends PluginBase implements Listener {

    /** @var string[] */
    private array $protectedWorldsPaintings = [];
    /** @var string[] */
    private array $protectedWorldsFrames = [];

    public function onEnable(): void {
        $this->saveResource("config.yml");
        $this->reloadConfig();

        $this->protectedWorldsPaintings = $this->getConfig()->get("protected-painting-worlds", []);
        $this->protectedWorldsFrames = $this->getConfig()->get("protected-itemframe-worlds", []);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if ($block->getTypeId() === BlockTypeIds::ENCHANTING_TABLE) {
            $event->cancel();
            $player->sendMessage("§cEnchanting is disabled!");
            return;
        }

        if ($block->getTypeId() === BlockTypeIds::ITEM_FRAME) {
            $world = $player->getWorld()->getFolderName();
            if (in_array($world, $this->protectedWorldsFrames)) {
                $event->cancel();
                $player->sendMessage("§cSorry - you cannot interact with item frames in this world!");
            }
        }
    }

    public function onEntityDamage(EntityDamageByEntityEvent $event): void {
        $entity = $event->getEntity();
        $world = $entity->getWorld()->getFolderName();
    
        if ($entity instanceof Painting && in_array($world, $this->protectedWorldsPaintings)) {
            $event->cancel();
            return;
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        $cmd = strtolower($command->getName());

        if ($cmd !== "paintprotect" && $cmd !== "itemframeprotect") {
            return false;
        }

        if (!isset($args[0])) {
            $sender->sendMessage("§eUsage: /$cmd <add|remove|list> [world]");
            return true;
        }

        $action = strtolower($args[0]);
        $list = $cmd === "paintprotect" ? $this->protectedWorldsPaintings : $this->protectedWorldsFrames;

        if ($action === "list") {
            $sender->sendMessage("§aProtected Worlds ($cmd): §f" . (count($list) ? implode(", ", $list) : "None"));
            return true;
        }

        if (!isset($args[1])) {
            $sender->sendMessage("§cPlease specify a world name.");
            return true;
        }

        $world = $args[1];

        if ($action === "add") {
            if (in_array($world, $list)) {
                $sender->sendMessage("§cWorld '$world' is already protected.");
            } else {
                $list[] = $world;
                $sender->sendMessage("§aAdded '$world' to protected worlds.");
            }
        } elseif ($action === "remove") {
            if (!in_array($world, $list)) {
                $sender->sendMessage("§cWorld '$world' is not protected.");
            } else {
                $list = array_values(array_diff($list, [$world]));
                $sender->sendMessage("§aRemoved '$world' from protected worlds.");
            }
        } else {
            $sender->sendMessage("§eUsage: /$cmd <add|remove|list> [world]");
        }

        if ($cmd === "paintprotect") {
            $this->protectedWorldsPaintings = $list;
            $this->updateConfig("protected-painting-worlds", $list);
        } else {
            $this->protectedWorldsFrames = $list;
            $this->updateConfig("protected-itemframe-worlds", $list);
        }

        return true;
    }

    private function updateConfig(string $key, array $list): void {
        $this->getConfig()->set($key, $list);
        $this->getConfig()->save();
    }
}
