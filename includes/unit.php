<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/audit.php';

class UnitOfMeasure {
    
    public static function getAll($activeOnly = true) {
        try {
            $sql = "SELECT * FROM public.unit_of_measures WHERE 1=1";
            $params = [];
            
            if ($activeOnly) {
                $sql .= " AND is_active = true";
            }
            
            $sql .= " ORDER BY unit_name";
            
            $stmt = Database::query($sql, $params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get units error: " . $e->getMessage());
            return [];
        }
    }
    
    public static function getById($id) {
        try {
            $sql = "SELECT * FROM public.unit_of_measures WHERE id = :id";
            $stmt = Database::query($sql, ['id' => $id]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get unit by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function getByCode($unitCode) {
        try {
            $sql = "SELECT * FROM public.unit_of_measures WHERE unit_code = :unit_code";
            $stmt = Database::query($sql, ['unit_code' => $unitCode]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get unit by code error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function create($data) {
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();
            
            // Set created_by
            $data['created_by'] = $_SESSION['user_id'] ?? 1;
            
            $sql = "INSERT INTO public.unit_of_measures 
                    (unit_code, unit_name, description, is_active, created_by) 
                    VALUES (:unit_code, :unit_name, :description, :is_active, :created_by) 
                    RETURNING id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $unitId = $stmt->fetchColumn();
            
            if (!$unitId) {
                throw new Exception("Failed to get unit ID after insertion");
            }
            
            // Audit trail
            AuditTrail::log('unit_of_measures', $unitId, 'CREATE', null, $data);
            
            $pdo->commit();
            return $unitId;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Create unit error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function update($id, $data) {
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();
            
            // Get old values for audit trail
            $oldUnit = self::getById($id);
            if (!$oldUnit) {
                throw new Exception("Unit not found with ID: " . $id);
            }
            
            // Set updated_by
            $data['updated_by'] = $_SESSION['user_id'] ?? 1;
            $data['id'] = $id;
            
            $sql = "UPDATE public.unit_of_measures SET 
                    unit_code = :unit_code,
                    unit_name = :unit_name,
                    description = :description,
                    is_active = :is_active,
                    updated_by = :updated_by,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($data);
            
            if (!$result) {
                throw new Exception("Update query failed");
            }
            
            // Audit trail
            $newValues = array_intersect_key($data, $oldUnit);
            AuditTrail::log('unit_of_measures', $id, 'UPDATE', $oldUnit, $newValues);
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Update unit error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function delete($id) {
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();
            
            // Get unit data for audit trail before deletion
            $unit = self::getById($id);
            if (!$unit) {
                throw new Exception("Unit not found with ID: " . $id);
            }
            
            // Check if unit is used in products
            $checkSql = "SELECT COUNT(*) as usage_count FROM public.products WHERE unit_id = :unit_id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute(['unit_id' => $id]);
            $usageCount = $checkStmt->fetchColumn();
            
            if ($usageCount > 0) {
                throw new Exception("Cannot delete unit. It is used in " . $usageCount . " product(s).");
            }
            
            $sql = "DELETE FROM public.unit_of_measures WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute(['id' => $id]);
            
            if (!$result) {
                throw new Exception("Delete query failed");
            }
            
            // Audit trail
            AuditTrail::log('unit_of_measures', $id, 'DELETE', $unit, null);
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Delete unit error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function softDelete($id) {
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();
            
            // Get old values for audit trail
            $oldUnit = self::getById($id);
            if (!$oldUnit) {
                throw new Exception("Unit not found with ID: " . $id);
            }
            
            $sql = "UPDATE public.unit_of_measures SET 
                    is_active = false,
                    updated_by = :updated_by,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $params = [
                'updated_by' => $_SESSION['user_id'] ?? 1,
                'id' => $id
            ];
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new Exception("Soft delete query failed");
            }
            
            // Audit trail
            AuditTrail::log('unit_of_measures', $id, 'SOFT_DELETE', $oldUnit, ['is_active' => false]);
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Soft delete unit error: " . $e->getMessage());
            return false;
        }
    }
}
?>