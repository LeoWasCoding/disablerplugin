<?php

namespace LeoWasCoding\disableenchant;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\object\Painting;
use pocketmine\block\BlockTypeIds;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Main extends PluginBase implements Listener {

    /** @var string[] */
    private array $protectedWorlds = [];

    public function onEnable(): void {
        $this->saveResource("config.yml");
        $this->reloadConfig();
        $this->protectedWorlds = $this->getConfig()->get("protected-painting-worlds", []);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $block = $event->getBlock();
        if ($block->getTypeId() === BlockTypeIds::ENCHANTING_TABLE) {
            $event->cancel();
            $event->getPlayer()->sendMessage("§cEnchanting is disabled!");
        }
    }

    public function onEntityDamage(EntityDamageByEntityEvent $event): void {
        $entity = $event->getEntity();
        $world = $entity->getWorld()->getFolderName();

        if ($entity instanceof Painting && in_array($world, $this->protectedWorlds)) {
            $event->cancel();
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (strtolower($command->getName()) !== "paintprotect") {
            return false;
        }

        if (!isset($args[0])) {
            $sender->sendMessage("§eUsage: /paintprotect <add|remove|list> [world]");
            return true;
        }

        $action = strtolower($args[0]);

        if ($action === "list") {
            $sender->sendMessage("§aProtected Worlds: §f" . implode(", ", $this->protectedWorlds));
            return true;
        }

        if (!isset($args[1])) {
            $sender->sendMessage("§cPlease specify a world name.");
            return true;
        }

        $world = $args[1];

        if ($action === "add") {
            if (in_array($world, $this->protectedWorlds)) {
                $sender->sendMessage("§cWorld '$world' is already protected.");
            } else {
                $this->protectedWorlds[] = $world;
                $this->updateConfig();
                $sender->sendMessage("§aAdded '$world' to protected worlds.");
            }
        } elseif ($action === "remove") {
            if (!in_array($world, $this->protectedWorlds)) {
                $sender->sendMessage("§cWorld '$world' is not protected.");
            } else {
                $this->protectedWorlds = array_values(array_diff($this->protectedWorlds, [$world]));
                $this->updateConfig();
                $sender->sendMessage("§aRemoved '$world' from protected worlds.");
            }
        } else {
            $sender->sendMessage("§eUsage: /paintprotect <add|remove|list> [world]");
        }

        return true;
    }

    private function updateConfig(): void {
        $this->getConfig()->set("protected-painting-worlds", $this->protectedWorlds);
        $this->getConfig()->save();
    }
}