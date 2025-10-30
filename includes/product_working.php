<?php
require_once __DIR__ . '/../config/database.php';

class ProductWorking {
    
    public static function create($data) {
        try {
            $pdo = Database::getInstance();
            
            // Generate product code if not provided
            if (empty($data['product_code'])) {
                $data['product_code'] = self::generateProductCode();
            }
            
            // Set created_by
            $data['created_by'] = $_SESSION['user_id'] ?? 1;
            
            // Set default values
            $data['is_active'] = $data['is_active'] ?? true;
            $data['category_id'] = !empty($data['category_id']) ? $data['category_id'] : null;
            $data['min_stock_level'] = $data['min_stock_level'] ?? 0;
            
            // Build SQL dynamically based on available data
            $fields = [];
            $values = [];
            $params = [];
            
            $fieldMap = [
                'product_code', 'product_name', 'description', 'category_id', 
                'unit_price', 'cost_price', 'stock_quantity', 'min_stock_level',
                'max_stock_level', 'barcode', 'sku', 'weight', 'dimensions',
                'supplier_id', 'created_by', 'is_active'
            ];
            
            foreach ($fieldMap as $field) {
                if (isset($data[$field]) && $data[$field] !== '') {
                    $fields[] = $field;
                    $values[] = ":$field";
                    $params[$field] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                throw new Exception("No valid fields to insert");
            }
            
            $sql = "INSERT INTO public.products (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $values) . ") 
                    RETURNING id";
            
            error_log("ProductWorking SQL: " . $sql);
            error_log("ProductWorking params: " . print_r($params, true));
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $productId = $stmt->fetchColumn();
            
            if ($productId) {
                // Simple audit without transaction
                self::simpleAudit('products', $productId, 'CREATE', $data);
                return $productId;
            } else {
                throw new Exception("No product ID returned");
            }
            
        } catch (Exception $e) {
            error_log("ProductWorking create error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function update($id, $data) {
        try {
            $pdo = Database::getInstance();
            
            // Get old product for audit
            $oldProduct = self::getById($id);
            if (!$oldProduct) {
                throw new Exception("Product not found");
            }
            
            // Set updated_by
            $data['updated_by'] = $_SESSION['user_id'] ?? 1;
            
            // Build update SQL dynamically
            $updates = [];
            $params = ['id' => $id];
            
            $fieldMap = [
                'product_name', 'description', 'category_id', 'unit_price', 'cost_price',
                'stock_quantity', 'min_stock_level', 'max_stock_level', 'barcode', 'sku',
                'weight', 'dimensions', 'supplier_id', 'is_active', 'updated_by'
            ];
            
            foreach ($fieldMap as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    $params[$field] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                throw new Exception("No fields to update");
            }
            
            $sql = "UPDATE public.products SET 
                    " . implode(', ', $updates) . ",
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            error_log("ProductWorking update SQL: " . $sql);
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                self::simpleAudit('products', $id, 'UPDATE', $data);
                return true;
            } else {
                return false;
            }
            
        } catch (Exception $e) {
            error_log("ProductWorking update error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getById($id) {
        try {
            $sql = "SELECT * FROM public.products WHERE id = :id";
            $stmt = Database::query($sql, ['id' => $id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("ProductWorking getById error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function generateProductCode() {
        try {
            $prefix = 'PRD';
            $sql = "SELECT COUNT(*) as count FROM public.products WHERE product_code LIKE :prefix";
            $stmt = Database::query($sql, ['prefix' => $prefix . '%']);
            $count = $stmt->fetchColumn();
            
            return $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            return $prefix . '-' . time();
        }
    }
    
    private static function simpleAudit($tableName, $recordId, $action, $data) {
        try {
            $sql = "INSERT INTO public.audit_trail 
                    (table_name, record_id, action, new_values, changed_by, ip_address) 
                    VALUES (:table_name, :record_id, :action, :new_values, :changed_by, :ip_address)";
            
            Database::query($sql, [
                'table_name' => $tableName,
                'record_id' => $recordId,
                'action' => $action,
                'new_values' => json_encode($data),
                'changed_by' => $_SESSION['user_id'] ?? 1,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {
            // Ignore audit errors
            error_log("Simple audit error: " . $e->getMessage());
        }
    }
}
?>