<?php
require_once __DIR__ . '/../config/database.php';

class ProductSimple {
    
    public static function create($data) {
        try {
            $pdo = Database::getInstance();
            
            // Generate product code
            $productCode = $data['product_code'] ?? self::generateProductCode();
            
            // Set required fields
            $productName = $data['product_name'];
            $unitPrice = (float) $data['unit_price'];
            $costPrice = (float) $data['cost_price'];
            $stockQuantity = (int) ($data['stock_quantity'] ?? 0);
            $createdBy = $_SESSION['user_id'] ?? 1;
            
            $sql = "INSERT INTO public.products 
                    (product_code, product_name, unit_price, cost_price, stock_quantity, created_by) 
                    VALUES (:product_code, :product_name, :unit_price, :cost_price, :stock_quantity, :created_by) 
                    RETURNING id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'product_code' => $productCode,
                'product_name' => $productName,
                'unit_price' => $unitPrice,
                'cost_price' => $costPrice,
                'stock_quantity' => $stockQuantity,
                'created_by' => $createdBy
            ]);
            
            $productId = $stmt->fetchColumn();
            
            // Simple audit trail tanpa transaction
            if ($productId) {
                self::simpleAudit('products', $productId, 'CREATE', $data);
            }
            
            return $productId;
            
        } catch (Exception $e) {
            error_log("ProductSimple create error: " . $e->getMessage());
            return false;
        }
    }
    
    private static function generateProductCode() {
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
    
    public static function update($id, $data) {
        try {
            $pdo = Database::getInstance();
            
            $sql = "UPDATE public.products SET 
                    product_name = :product_name,
                    unit_price = :unit_price,
                    cost_price = :cost_price,
                    stock_quantity = :stock_quantity,
                    updated_by = :updated_by,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $result = Database::query($sql, [
                'product_name' => $data['product_name'],
                'unit_price' => $data['unit_price'],
                'cost_price' => $data['cost_price'],
                'stock_quantity' => $data['stock_quantity'],
                'updated_by' => $_SESSION['user_id'] ?? 1,
                'id' => $id
            ]);
            
            if ($result) {
                self::simpleAudit('products', $id, 'UPDATE', $data);
            }
            
            return $result !== false;
            
        } catch (Exception $e) {
            error_log("ProductSimple update error: " . $e->getMessage());
            return false;
        }
    }
}
?>