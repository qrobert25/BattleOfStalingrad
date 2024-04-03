<?php

namespace App\Services;

class GameTank
{
    private $playerName;
    private $name;
    private $health = 100;
    private $armor = 0;
    private $damage = 20;
    private $fuelRange = 6;
    private $fireRange = 3;
    private $position = array(0, 0);
    private $score = 0;

    public function __construct(String $playerName, String $name, $attributes = array(), $position = array(0, 0))
    {
        $this->playerName = $playerName;
        $this->name = $name;
        $this->position = $position;

        if (!empty($attributes['armor']) && is_int($attributes['armor'])) {
            $this->armor = $attributes['armor'];
        }

        if (!empty($attributes['damage']) && is_int($attributes['damage'])) {
            $this->damage = $attributes['damage'];
        }

        if (!empty($attributes['fuel_range']) && is_int($attributes['fuel_range'])) {
            $this->fuelRange = $attributes['fuel_range'];
        }

        if (!empty($attributes['fire_range']) && is_int($attributes['fire_range'])) {
            $this->fireRange = $attributes['fire_range'];
        }
    }

    public function checkShootingRange($target, $map)
    {
        $obstacles = false;
        $response = false;

        $distance = 0;
        $shooterPosition = $this->getPosition();
        $targetPosition = $target->getPosition();

        if ($shooterPosition[0] == $targetPosition[0]) {
            $distance = abs($shooterPosition[1] - $targetPosition[1]);

            // Check if there is an obstacle between the shooter and the target
            foreach (range($shooterPosition[1], $targetPosition[1]) as $i) {
                if ($map[$shooterPosition[0]][$i] == 9) {
                    $obstacles = true;
                    break;
                }
            }
        }

        if ($shooterPosition[1] == $targetPosition[1]) {
            $distance = abs($shooterPosition[0] - $targetPosition[0]);

            // Check if there is an obstacle between the shooter and the target
            foreach (range($shooterPosition[0], $targetPosition[0]) as $i) {
                if ($map[$i][$shooterPosition[1]] == 9) {
                    $obstacles = true;
                    break;
                }
            }
        }

        if (!$obstacles && $distance > 0 && $distance <= $this->fireRange) {
            $response = true;
        }

        return $response;
    }

    public function tryShooting(&$target, $map)
    {
        // Check if the target is within the fire range
        $inRange = $this->checkShootingRange($target, $map);

        if (!$inRange) {
            return;
        }
        
        // Check if the target has already been defeated
        if ($target->getHealth() <= 0) {
            // echo "<p>".$target->getName()." has already been defeated.</p>";
            return;
        }

        $armorReduction = 0;
        $damage = $this->damage;

        if ($target->getArmor() > 0) {
            $armorReduction = -10;
            $damage -= 10;
            $target->setArmor($target->getArmor() - 10);
        }

        $target->setHealth($target->getHealth() - $damage);

        // echo "<p>".$this->name." shot ".$target->getName()." and caused ".$damage." damage.";
        $this->updateScore(130);
        
        if ($armorReduction < 0) {
            // echo "<br> The armor of ".$target->getName()." has been reduced by ".$armorReduction;
            $this->updateScore(150);
            
            if ($target->getArmor() == 0) {
                // echo " and it has been depleted";
                $this->updateScore(180);
            }

            // echo ".";
        }

        // echo "<br>".$target->getName()." health is now ".$target->getHealth().".</p>";
        // echo "</p>";

        if ($target->getHealth() <= 0) {
            // echo "<p>".$target->getName()." has been defeated.</p>";
            // echo "<p>".$this->playerName." wins the game.</p>";
            // echo "<p>".$target->getPlayerName()." loses the game.</p>";
            $this->updateScore(200);
        }
    }

    public function getPlayerName()
    {
        return $this->playerName;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
        $this->updateScore(10);
        // // echo "<p>".$this->name." moved to position ".json_encode($this->position)."</p>";
    }

    public function getFuelRange()
    {
        return $this->fuelRange;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getHealth()
    {
        return $this->health;
    }

    public function setHealth($health)
    {
        if ($health < $this->health) {
            $this->updateScore(-15);
        }

        $this->health = $health;
    }

    public function getArmor()
    {
        return $this->armor;
    }

    public function setArmor($armor)
    {
        $this->armor = $armor;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function updateScore($score)
    {
        $this->score += $score;
    }
}
