<?php
namespace App\Services;

use DateTime;
use App\Helpers\DB;
use App\Services\Maps;
use App\Helpers\RedisDB;
use App\Services\Players;
use App\Services\BattleScorePlayers;
use Ramsey\Uuid\Uuid;

class BattleScores
{
    private $db;
    private $dbCollection = 'battle_scores';
    private $dailyLeaderboardKey = 'daily_leaderboard';
    private $weeklyLeaderboardKey = 'weekly_leaderboard';
    private $monthlyLeaderboardKey = 'monthly_leaderboard';

    public function __construct()
    {
        $this->db = new DB($this->dbCollection);
    }

    public function save(array $data = [])
    {
        $document = Uuid::uuid4();
        $document = $document->toString();

        $insert = [
            'game_session_id' => $data['game_session_id'],
            'map_id' => $data['map_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $result = $this->db->insert($document, $insert);

        $battleScorePlayers = new BattleScorePlayers();

        $player1 = json_decode($data['player_1'], true);
        $battleScorePlayers->save([
            'battle_score_id' => $document,
            'player_id' => $player1['id'],
            'score' => $player1['score']
        ]);

        $player2 = json_decode($data['player_2'], true);
        $battleScorePlayers->save([
            'battle_score_id' => $document,
            'player_id' => $player2['id'],
            'score' => $player2['score']
        ]);

        $this->deleteLeaderboardCache();
        return $result;
    }

    public function getScore($id)
    {
        $score = $this->db->getDocument($id);

        if (empty($score)) {
            return [];
        }

        // get the players scores associated with the battle score
        $mapsService = new Maps();
        $playersService = new Players();
        $battleScorePlayersService = new BattleScorePlayers();
        $bspCollectionName = $battleScorePlayersService->getDbCollectionName();
        $playerCollectionName = $playersService->getDbCollectionName();
        $mapsCollectionName = $mapsService->getDbCollectionName();

        $where = "WHERE 1 = 1";
        $where .= " AND bsp.battle_score_id = '" . $id . "'";

        $dbInstance = $this->db->getDbInstance();
        $query = "
            SELECT 
                META(bsp).id AS player_score_id,
                m.`name` AS map_name,
                p.`name` AS player_name,
                bsp.score
            FROM ".$dbInstance.".`".$this->dbCollection."` AS bs
            INNER JOIN ".$dbInstance.".`".$bspCollectionName."` AS bsp ON META(bs).id = bsp.battle_score_id 
            INNER JOIN ".$dbInstance.".`".$playerCollectionName."` AS p ON bsp.player_id = META(p).id
            INNER JOIN ".$dbInstance.".`".$mapsCollectionName."` AS m ON bs.map_id = META(m).id
            $where";

        $score['players'] = $this->db->query($query);
        return $score;
    }

    public function getDailyLeaderboard()
    {
        $redisDb = new RedisDB();
        $scores = $redisDb->get($this->dailyLeaderboardKey);

        if ($scores === false) {
            $fromDate = new DateTime(date('Y-m-d 00:00:00'));
            $toDate = new DateTime(date('Y-m-d 23:59:59'));
    
            $scores = $this->getLeaderboard([
                'fromDate' => $fromDate->format('Y-m-d 00:00:00'),
                'toDate' => $toDate->format('Y-m-d 23:59:59')
            ]);

            $now = $fromDate;
            $endOfDay = $toDate;
            $secondsUntilEndOfDay = $endOfDay->getTimestamp() - $now->getTimestamp();

            // Set the daily leaderboard key to expire at the end of the day
            $redisDb->set($this->dailyLeaderboardKey, json_encode($scores), $secondsUntilEndOfDay);
        } else {
            $scores = json_decode($scores, true);
        }

        return $scores;
    }

    public function getWeeklyLeaderboard()
    {
        $redisDb = new RedisDB();
        $scores = $redisDb->get($this->weeklyLeaderboardKey);

        if ($scores === false) {
            $fromDate = new DateTime(date('Y-m-d 00:00:00'));
            $fromDate->modify('Monday this week');
            $filters['fromDate'] = $fromDate->format('Y-m-d 00:00:00');

            $toDate = new DateTime(date('Y-m-d 23:59:59', strtotime($fromDate->format('Y-m-d'))));
            $toDate->modify('Sunday this week');
            $filters['toDate'] = $toDate->format('Y-m-d 23:59:59');

            $scores = $this->getLeaderboard($filters);

            $now = new DateTime();
            $endOfWeek = $toDate;
            $secondsUntilEndOfWeek = $endOfWeek->getTimestamp() - $now->getTimestamp();

            // Set the weekly leaderboard key to expire at the end of the day
            $redisDb->set($this->weeklyLeaderboardKey, json_encode($scores), $secondsUntilEndOfWeek);
        } else {
            $scores = json_decode($scores, true);
        }

        return $scores;
    }

    public function getMonthlyLeaderboard()
    {
        $redisDb = new RedisDB();
        $scores = $redisDb->get($this->monthlyLeaderboardKey);

        if ($scores === false) {
            $fromDate = new DateTime(date('Y-m-d 00:00:00'));
            $fromDate->modify('first day of this month');
            $filters['fromDate'] = $fromDate->format('Y-m-d 00:00:00');

            $toDate = new DateTime(date('Y-m-d 23:59:59', strtotime($fromDate->format('Y-m-d'))));
            $toDate->modify('last day of this month');
            $filters['toDate'] = $fromDate->format('Y-m-d 23:59:59');
            
            $scores = $this->getLeaderboard($filters);

            $now = new DateTime();
            $endOfMonth = $toDate;
            $secondsUntilEndOfMonth = $endOfMonth->getTimestamp() - $now->getTimestamp();

            // Set the weekly leaderboard key to expire at the end of the day
            $redisDb->set($this->monthlyLeaderboardKey, json_encode($scores), $secondsUntilEndOfMonth);
        } else {
            $scores = json_decode($scores, true);
        }
        
        return $scores;
    }

    public function getLeaderboard(array $filters = array())
    {
        $mapsService = new Maps();
        $playersService = new Players();
        $battleScorePlayersService = new BattleScorePlayers();
        $bspCollectionName = $battleScorePlayersService->getDbCollectionName();
        $playerCollectionName = $playersService->getDbCollectionName();
        $mapsCollectionName = $mapsService->getDbCollectionName();

        $where = "WHERE 1 = 1";

        if (!empty($filters['fromDate']) && !empty($filters['toDate'])) {
            $where .= " AND bs.created_at >= '" . date('Y-m-d 00:00:00', strtotime($filters['fromDate'])) . "'";
            $where .= " AND bs.created_at <= '" . date('Y-m-d 23:59:59', strtotime($filters['toDate'])) . "'";
        }

        $dbInstance = $this->db->getDbInstance();
        $query = "
            SELECT 
                META(bs).id AS scoreId,
                m.`name` AS map_name,
                p.`name` AS player_name,
                bsp.score
            FROM ".$dbInstance.".`".$this->dbCollection."` AS bs
            INNER JOIN ".$dbInstance.".`".$bspCollectionName."` AS bsp ON META(bs).id = bsp.battle_score_id 
            INNER JOIN ".$dbInstance.".`".$playerCollectionName."` AS p ON bsp.player_id = META(p).id
            INNER JOIN ".$dbInstance.".`".$mapsCollectionName."` AS m ON bs.map_id = META(m).id
            $where 
            ORDER BY bsp.score DESC LIMIT 5";

        $scores = $this->db->query($query);

        return $scores;
    }

    /**
     * removeAll
     * Removes all scores. It is only used in the seeder.
     */
    public function removeAll()
    {
        $instance = $this->db->getDbCollectionInstance();
        $this->db->query('DELETE FROM ' . $instance . ' WHERE 1 = 1');
    }

    public function deleteLeaderboardCache()
    {
        $redisDb = new RedisDB();
        $redisDb->delete($this->dailyLeaderboardKey);
        $redisDb->delete($this->weeklyLeaderboardKey);
        $redisDb->delete($this->monthlyLeaderboardKey);
    }
}