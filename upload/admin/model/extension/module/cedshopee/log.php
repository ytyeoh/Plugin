<?php
class ModelExtensionModuleCedshopeeLog extends Model {
    public function addLog($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "cedshopee_log SET 
             method = '" . $this->db->escape($data['method']) . "',
             message = '" . $this->db->escape($data['message']) . "', 
             response = '" . $this->db->escape(json_encode($data['response'])) . "' , 
             created_at = now()
       ");
    }

    public function deleteLog($log_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cedshopee_log WHERE id = '" . (int)$log_id. "'");
    }

    public function getLogs($data) {
        $sql = "SELECT * FROM " . DB_PREFIX . "cedshopee_log ";

        $sort_data = array(
            'method',
            'id',
            'created_at'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY method";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalLogs() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cedshopee_log");

        return $query->row['total'];
    }

    public function deleteAllLog() {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cedshopee_log ");
    }

}
?>