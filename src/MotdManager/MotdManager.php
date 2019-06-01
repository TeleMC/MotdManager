<?php
namespace MotdManager;

use pocketmine\command\{Command, CommandSender};
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class MotdManager extends PluginBase {

    public $pre = "§e•";
    //public $pre = "§l§e[ §f시스템 §e]§r§e";
    public $count = 0;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["setting" => false, "motd" => []]);
        $this->data = $this->config->getAll();
        $this->getScheduler()->scheduleRepeatingTask(
                new class($this) extends Task {
                    public function __construct(MotdManager $plugin) {
                        $this->plugin = $plugin;
                    }

                    public function onRun($currentTick) {
                        $this->plugin->tick();
                    }
                }, 100);
    }

    public function tick() {
        $notice = $this->data["motd"];
        if (count($this->data["motd"]) == 0) {
            $notice = ["Tele Server"];
        }
        $count = count($notice);
        if ($this->count >= $count)
            $this->count = 0;
        if (!isset($notice[$this->count]))
            return;
        $this->getServer()->getNetwork()->setName((string) $notice[$this->count]);
        $this->count++;
    }

    public function onCommand(CommandSender $sender, Command $cmd, $lebel, $args): bool {
        if ($cmd->getName() == "motd") {
            if (!$sender->isOp()) {
                $sender->sendMessage("{$this->pre} 권한이 없습니다.");
                return false;
            }
            if (!isset($args[0])) {
                $sender->sendMessage("--- MOTD 도움말 1 / 1 ---");
                $sender->sendMessage("{$this->pre} /motd add <서버명> | 서버명을 추가합니다.");
                $sender->sendMessage("{$this->pre} /motd remove <번호> | 서버명을 제거합니다.");
                $sender->sendMessage("{$this->pre} /motd list <인덱스> | 서버명을 확인합니다.");
                return false;
            }
            if ($args[0] == "add" || $args[0] == "a") {
                if (!isset($args[1])) {
                    $sender->sendMessage("{$this->pre} 서버명이 기재되지 않았습니다.");
                    return false;
                }
                unset($args[0]);
                $motd = implode(" ", $args);
                if (in_array($motd, $this->data["motd"])) {
                    $sender->sendMessage("{$this->pre} 이미 존재하는 서버명입니다.");
                    return false;
                }
                array_push($this->data["motd"], $motd);
                $this->save();
                $sender->sendMessage("{$this->pre} 서버명 [ §r{$motd} §r§e] (을)를 추가하였습니다.");
                return true;
            } elseif ($args[0] == "remove" || $args[0] == "r") {
                if (!isset($args[1]) || !is_numeric($args[1])) {
                    $sender->sendMessage("{$this->pre} 번호는 숫자여야합니다.");
                    return false;
                }
                if (!isset($this->data["motd"][$args[1]])) {
                    $sender->sendMessage("{$this->pre} 해당 번호의 서버명이 존재하지 않습니다.");
                    return false;
                }
                $motd = $this->data["motd"][$args[1]];
                unset($this->data["motd"][$args[1]]);
                sort($this->data["motd"]);
                $this->save();
                $sender->sendMessage("{$this->pre} 서버명 [ §r{$motd} §r§e] (을)를 제거하였습니다.");
                return true;
            } elseif ($args[0] == "list" || $args[0] == "l") {
                if (count($this->data["motd"]) == 0) {
                    $sender->sendMessage("--- MOTD 목록 1 / 1 ---");
                    $sender->sendMessage("{$this->pre} 서버명이 존재하지 않습니다.");
                    return true;
                }
                $maxpage = ceil(count($this->data["motd"]) / 5);
                if (!isset($args[1]) || !is_numeric($args[1]) || $args[1] <= 0) {
                    $page = 1;
                } elseif ($args[1] > $maxpage) {
                    $page = $maxpage;
                } else {
                    $page = $args[1];
                }
                $motd = "";
                $count = 0;
                foreach ($this->data["motd"] as $key => $value) {
                    if ($page * 5 - 5 <= $count and $count < $page * 5) {
                        $motd .= "§l§e[§f{$key}번§e] §r§e[ §f{$value} §r§e]\n";
                        $count++;
                    } else {
                        $count++;
                        continue;
                    }
                }
                $sender->sendMessage("--- MOTD 목록 {$page} / {$maxpage} ---");
                $sender->sendMessage($motd);
                return true;
            } else {
                $sender->sendMessage("--- MOTD 도움말 1 / 1 ---");
                $sender->sendMessage("{$this->pre} /motd add <서버명> | 서버명을 추가합니다.");
                $sender->sendMessage("{$this->pre} /motd remove <번호> | 서버명을 제거합니다.");
                $sender->sendMessage("{$this->pre} /motd list <인덱스> | 서버명을 확인합니다.");
                return false;
            }
            return false;
        }
        return false;
    }

    public function save() {
        $this->config->setAll($this->data);
        $this->config->save();
    }
}
