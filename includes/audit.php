<?php
require_once __DIR__ . '/../config/database.php';

class AuditTrail
{

    public static function log($tableName, $recordId, $action, $oldValues = null, $newValues = null, $changedBy = null)
    {
        try {
            $changedBy = $changedBy ?? ($_SESSION['user_id'] ?? 1);

            $sql = "INSERT INTO public.audit_trail 
                    (table_name, record_id, action, old_values, new_values, changed_by, ip_address, user_agent) 
                    VALUES (:table_name, :record_id, :action, :old_values, :new_values, :changed_by, :ip_address, :user_agent)";

            $params = [
                'table_name' => $tableName,
                'record_id' => (int) $recordId,
                'action' => $action,
                'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'changed_by' => (int) $changedBy,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
            ];

            // Debug audit trail
            error_log("Audit trail data: " . print_r($params, true));

            $result = Database::query($sql, $params);
            return $result !== false;
        } catch (PDOException $e) {
            error_log("Audit trail error: " . $e->getMessage());
            return false;
        }
    }

    // ... getAuditLog method tetap sama ...

    public static function getAuditLog($tableName = null, $recordId = null, $limit = 100)
    {
        try {
            $sql = "SELECT at.*, u.name as changed_by_name 
                    FROM public.audit_trail at 
                    LEFT JOIN public.users u ON at.changed_by = u.id 
                    WHERE 1=1";

            $params = [];

            if ($tableName) {
                $sql .= " AND at.table_name = :table_name";
                $params['table_name'] = $tableName;
            }

            if ($recordId) {
                $sql .= " AND at.record_id = :record_id";
                $params['record_id'] = $recordId;
            }

            $sql .= " ORDER BY at.changed_at DESC LIMIT :limit";
            $params['limit'] = $limit;

            $stmt = Database::query($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get audit log error: " . $e->getMessage());
            return [];
        }
    }
}
