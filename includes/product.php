<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/audit.php';

class Product
{
    /**
     * Get all product categories
     */
    public static function getCategories()
    {
        try {
            $pdo = Database::getInstance();

            // PERBAIKAN: ganti 'categories' dengan 'product_category'
            $sql = "SELECT * FROM public.product_categories WHERE is_active = true ORDER BY category_name";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Product::getCategories() Error: " . $e->getMessage());
            return [];
        }
    }

    // Update getAll method untuk include unit name
    public static function getAll($activeOnly = true)
    {
        try {
            $sql = "SELECT p.*, pc.category_name, uom.unit_code, uom.unit_name 
                FROM public.products p 
                JOIN public.product_categories pc ON p.category_id = pc.id 
                JOIN public.unit_of_measures uom ON p.unit_id = uom.id -- TAMBAH JOIN INI
                WHERE 1=1";

            $params = [];

            if ($activeOnly) {
                $sql .= " AND p.is_active = true";
            }

            $sql .= " ORDER BY p.product_name";

            $stmt = Database::query($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get products error: " . $e->getMessage());
            return [];
        }
    }

    // Update getById method untuk include unit name
    public static function getById($id)
    {
        try {
            $pdo = Database::getInstance();

            $sql = "SELECT * FROM public.products WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Product::getById() Error: " . $e->getMessage());
            return null;
        }
    }

    // Di method create(), tambahkan unit_id:
    public static function create($data)
    {
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();

            // Generate product code if not provided
            if (empty($data['product_code'])) {
                $data['product_code'] = self::generateProductCode();
            }

            // Ensure user_id is set
            if (!isset($data['created_by'])) {
                $data['created_by'] = $_SESSION['user_id'] ?? 1;
            }

            // Set default values untuk SEMUA field yang required oleh database
            $data['is_active'] = $data['is_active'] ?? true;
            $data['category_id'] = isset($data['category_id']) && $data['category_id'] !== '' ? $data['category_id'] : null;
            $data['unit_id'] = isset($data['unit_id']) && $data['unit_id'] !== '' ? $data['unit_id'] : null;
            $data['supplier_id'] = isset($data['supplier_id']) && $data['supplier_id'] !== '' ? $data['supplier_id'] : null;
            $data['weight'] = isset($data['weight']) && $data['weight'] !== '' ? $data['weight'] : null;
            $data['max_stock_level'] = isset($data['max_stock_level']) && $data['max_stock_level'] !== '' ? $data['max_stock_level'] : null;
            $data['min_stock_level'] = $data['min_stock_level'] ?? 0;
            $data['description'] = $data['description'] ?? null;
            $data['barcode'] = $data['barcode'] ?? null;
            $data['sku'] = $data['sku'] ?? null;
            $data['dimensions'] = $data['dimensions'] ?? null;

            // Pastikan semua field yang ada di table ada di data
            $completeData = [
                'product_code' => $data['product_code'],
                'product_name' => $data['product_name'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'unit_id' => $data['unit_id'],
                'unit_price' => $data['unit_price'],
                'cost_price' => $data['cost_price'],
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'min_stock_level' => $data['min_stock_level'],
                'max_stock_level' => $data['max_stock_level'],
                'barcode' => $data['barcode'],
                'sku' => $data['sku'],
                'weight' => $data['weight'],
                'dimensions' => $data['dimensions'],
                'supplier_id' => $data['supplier_id'],
                'created_by' => $data['created_by'],
                'is_active' => $data['is_active']
            ];

            error_log("Product::create - Complete data: " . print_r($completeData, true));

            $sql = "INSERT INTO public.products 
                (product_code, product_name, description, category_id, unit_id, unit_price, cost_price, 
                 stock_quantity, min_stock_level, max_stock_level, barcode, sku, weight, 
                 dimensions, supplier_id, created_by, is_active) 
                VALUES (:product_code, :product_name, :description, :category_id, :unit_id, :unit_price, 
                        :cost_price, :stock_quantity, :min_stock_level, :max_stock_level, 
                        :barcode, :sku, :weight, :dimensions, :supplier_id, :created_by, :is_active) 
                RETURNING id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($completeData);
            $productId = $stmt->fetchColumn();

            if (!$productId) {
                throw new Exception("Failed to get product ID after insertion");
            }

            error_log("Product::create - Insert successful, ID: " . $productId);

            // Audit trail dengan error handling yang better
            try {
                require_once __DIR__ . '/audit.php';
                $auditResult = AuditTrail::log('products', $productId, 'CREATE', null, $completeData);
                error_log("Product::create - Audit trail result: " . ($auditResult ? 'SUCCESS' : 'FAILED'));
            } catch (Exception $auditException) {
                error_log("Product::create - Audit trail exception: " . $auditException->getMessage());
            }

            $pdo->commit();
            error_log("Product::create - Transaction committed successfully");
            return $productId;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                error_log("Product::create - Transaction rolled back due to error: " . $e->getMessage());
            }
            error_log("Create product error: " . $e->getMessage());
            error_log("Product data: " . print_r($data, true));
            return false;
        }
    }

    public static function update($id, $data)
    {
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();

            // Get old values for audit trail
            $oldProduct = self::getById($id);
            if (!$oldProduct) {
                throw new Exception("Product not found with ID: " . $id);
            }

            // Set updated_by
            $data['updated_by'] = $_SESSION['user_id'] ?? 1;

            // Gunakan nilai dari oldProduct sebagai fallback untuk semua field
            $updateData = [
                'product_name' => $data['product_name'] ?? $oldProduct['product_name'],
                'description' => $data['description'] ?? $oldProduct['description'],
                'category_id' => isset($data['category_id']) && $data['category_id'] !== '' ? $data['category_id'] : $oldProduct['category_id'],
                'unit_id' => isset($data['unit_id']) && $data['unit_id'] !== '' ? $data['unit_id'] : $oldProduct['unit_id'], // TAMBAH INI
                'unit_price' => $data['unit_price'] ?? $oldProduct['unit_price'],
                'cost_price' => $data['cost_price'] ?? $oldProduct['cost_price'],
                'stock_quantity' => $data['stock_quantity'] ?? $oldProduct['stock_quantity'],
                'min_stock_level' => $data['min_stock_level'] ?? $oldProduct['min_stock_level'],
                'max_stock_level' => isset($data['max_stock_level']) && $data['max_stock_level'] !== '' ? $data['max_stock_level'] : $oldProduct['max_stock_level'],
                'barcode' => $data['barcode'] ?? $oldProduct['barcode'],
                'sku' => $data['sku'] ?? $oldProduct['sku'],
                'weight' => isset($data['weight']) && $data['weight'] !== '' ? $data['weight'] : $oldProduct['weight'],
                'dimensions' => $data['dimensions'] ?? $oldProduct['dimensions'],
                'supplier_id' => isset($data['supplier_id']) && $data['supplier_id'] !== '' ? $data['supplier_id'] : $oldProduct['supplier_id'],
                'is_active' => $data['is_active'] ?? $oldProduct['is_active'],
                'updated_by' => $data['updated_by'],
                'id' => $id
            ];

            error_log("Product::update - Update data: " . print_r($updateData, true));

            $sql = "UPDATE public.products SET 
                product_name = :product_name,
                description = :description,
                category_id = :category_id,
                unit_id = :unit_id, -- TAMBAH INI
                unit_price = :unit_price,
                cost_price = :cost_price,
                stock_quantity = :stock_quantity,
                min_stock_level = :min_stock_level,
                max_stock_level = :max_stock_level,
                barcode = :barcode,
                sku = :sku,
                weight = :weight,
                dimensions = :dimensions,
                supplier_id = :supplier_id,
                is_active = :is_active,
                updated_by = :updated_by,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($updateData);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Update query failed: " . $errorInfo[2]);
            }

            $rowCount = $stmt->rowCount();
            error_log("Update executed - Rows affected: " . $rowCount);

            if ($rowCount === 0) {
                // Mungkin data tidak berubah, tapi bukan error
                error_log("No rows affected - data mungkin unchanged");
            }

            // **FIX: Audit trail dengan error handling**
            try {
                $changedFields = [];
                foreach ($updateData as $key => $value) {
                    if ($key !== 'updated_by' && $key !== 'id') {
                        $oldValue = $oldProduct[$key] ?? null;
                        if ($oldValue != $value) {
                            $changedFields[$key] = [
                                'old' => $oldValue,
                                'new' => $value
                            ];
                        }
                    }
                }

                if (!empty($changedFields)) {
                    $auditResult = AuditTrail::log('products', $id, 'UPDATE', $oldProduct, $changedFields);
                    error_log("Product::update - Audit trail result: " . ($auditResult ? 'SUCCESS' : 'FAILED'));
                }
            } catch (Exception $auditException) {
                error_log("Product::update - Audit trail exception: " . $auditException->getMessage());
                // **JANGAN rollback hanya karena audit gagal!**
            }

            $pdo->commit();
            error_log("Product::update - Transaction committed successfully");
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                error_log("Product::update - Transaction rolled back due to error");
            }
            error_log("Update product error: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();

            // Get product data for audit trail before deletion
            $product = self::getById($id);
            if (!$product) {
                throw new Exception("Product not found with ID: " . $id);
            }

            $sql = "DELETE FROM public.products WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute(['id' => $id]);

            if (!$result) {
                throw new Exception("Delete query failed");
            }

            error_log("Product delete - Delete successful for ID: " . $id);

            // Log to audit trail
            try {
                $auditResult = AuditTrail::log('products', $id, 'DELETE', $product, null);
                error_log("Product delete - Audit trail result: " . ($auditResult ? 'SUCCESS' : 'FAILED'));
            } catch (Exception $auditException) {
                error_log("Product delete - Audit trail exception: " . $auditException->getMessage());
            }

            $pdo->commit();
            error_log("Product delete - Transaction committed successfully");
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                error_log("Product delete - Transaction rolled back due to error");
            }
            error_log("Delete product error: " . $e->getMessage());
            return false;
        }
    }

    public static function softDelete($id)
    {
        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();

            // Get old values for audit trail
            $oldProduct = self::getById($id);
            if (!$oldProduct) {
                throw new Exception("Product not found with ID: " . $id);
            }

            $sql = "UPDATE public.products SET 
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

            error_log("Product softDelete - Soft delete successful for ID: " . $id);

            // Log to audit trail
            try {
                $auditResult = AuditTrail::log('products', $id, 'SOFT_DELETE', $oldProduct, ['is_active' => false]);
                error_log("Product softDelete - Audit trail result: " . ($auditResult ? 'SUCCESS' : 'FAILED'));
            } catch (Exception $auditException) {
                error_log("Product softDelete - Audit trail exception: " . $auditException->getMessage());
            }

            $pdo->commit();
            error_log("Product softDelete - Transaction committed successfully");
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                error_log("Product softDelete - Transaction rolled back due to error");
            }
            error_log("Soft delete product error: " . $e->getMessage());
            return false;
        }
    }

    public static function simpleCreate($productName, $unitPrice, $costPrice, $stockQuantity = 0)
    {
        try {
            $pdo = Database::getInstance();

            $productCode = self::generateProductCode();
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

            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Simple create error: " . $e->getMessage());
            return false;
        }
    }

    public static function search($keyword, $categoryId = null)
    {
        try {
            $sql = "SELECT p.*, pc.category_name 
                    FROM public.products p 
                    JOIN public.product_categories pc ON p.category_id = pc.id 
                    WHERE p.is_active = true 
                    AND (p.product_name ILIKE :keyword OR p.product_code ILIKE :keyword OR p.description ILIKE :keyword)";

            $params = ['keyword' => '%' . $keyword . '%'];

            if ($categoryId) {
                $sql .= " AND p.category_id = :category_id";
                $params['category_id'] = $categoryId;
            }

            $sql .= " ORDER BY p.product_name";

            $stmt = Database::query($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search products error: " . $e->getMessage());
            return [];
        }
    }

    public static function getLowStock($threshold = 10)
    {
        try {
            $sql = "SELECT p.*, pc.category_name 
                    FROM public.products p 
                    LEFT JOIN public.product_categories pc ON p.category_id = pc.id 
                    WHERE p.is_active = true 
                    AND (p.stock_quantity <= p.min_stock_level OR p.stock_quantity <= :threshold)
                    ORDER BY p.stock_quantity ASC";

            $stmt = Database::query($sql, ['threshold' => $threshold]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get low stock products error: " . $e->getMessage());
            return [];
        }
    }

    public static function generateProductCode()
    {
        global $pdo;

        try {
            $sql = "SELECT product_code FROM products WHERE product_code LIKE 'PRO-%' ORDER BY id DESC LIMIT 1";
            $stmt = $pdo->query($sql);

            if ($stmt) {
                $lastProduct = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($lastProduct && preg_match('/PRO-(\d+)/', $lastProduct['product_code'], $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                } else {
                    $nextNumber = 1;
                }

                return 'PRO-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            } else {
                // Jika query gagal, return default code
                return 'PRO-001';
            }
        } catch (Exception $e) {
            error_log("Error in generateProductCode: " . $e->getMessage());
            return 'PRO-001';
        }
    }

    public static function getStats()
    {
        try {
            $stats = [];

            // Total products
            $sql = "SELECT COUNT(*) as total FROM public.products WHERE is_active = true";
            $stmt = Database::query($sql);
            $stats['total_products'] = $stmt->fetchColumn();

            // Active products
            $sql = "SELECT COUNT(*) as active FROM public.products WHERE is_active = true";
            $stmt = Database::query($sql);
            $stats['active_products'] = $stmt->fetchColumn();

            // Out of stock products
            $sql = "SELECT COUNT(*) as out_of_stock FROM public.products WHERE is_active = true AND stock_quantity = 0";
            $stmt = Database::query($sql);
            $stats['out_of_stock'] = $stmt->fetchColumn();

            // Low stock products
            $sql = "SELECT COUNT(*) as low_stock FROM public.products WHERE is_active = true AND stock_quantity > 0 AND stock_quantity <= min_stock_level";
            $stmt = Database::query($sql);
            $stats['low_stock'] = $stmt->fetchColumn();

            return $stats;
        } catch (PDOException $e) {
            error_log("Get product stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get product vendors dengan join ke partners
     */
    public static function getProductVendors($productId)
    {
        try {
            $pdo = Database::getInstance();

            $sql = "SELECT pv.*, p.partner_name, p.partner_code, p.contact_person, p.phone, p.email 
                FROM public.product_vendors pv 
                JOIN public.partners p ON pv.partner_id = p.id 
                WHERE pv.product_id = ? 
                ORDER BY pv.is_primary DESC, p.partner_name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Product::getProductVendors() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add vendor to product
     */
    public static function addVendor($productId, $partnerId, $data)
    {
        try {
            $pdo = Database::getInstance();

            error_log("=== ADD VENDOR METHOD DEBUG ===");
            error_log("Product ID: $productId, Partner ID: $partnerId");
            error_log("Vendor Data: " . print_r($data, true));

            // Validasi product exists
            $product = self::getById($productId);
            if (!$product) {
                error_log("ERROR: Product with ID $productId not found");
                return false;
            }
            error_log("Product found: " . $product['product_name']);

            // Validasi partner exists
            require_once __DIR__ . '/../partner.php';
            $partner = Partner::getById($partnerId);
            if (!$partner) {
                error_log("ERROR: Partner with ID $partnerId not found");
                return false;
            }
            error_log("Partner found: " . $partner['partner_name']);

            // Cek tabel exists
            $tableCheck = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables 
                              WHERE table_name = 'product_vendors')")->fetchColumn();

            if (!$tableCheck) {
                error_log("ERROR: product_vendors table does not exist!");
                return false;
            }
            error_log("Table product_vendors exists");

            // Jika set as primary, update yang lain menjadi non-primary
            if ($data['is_primary'] ?? false) {
                $updateSql = "UPDATE product_vendors SET is_primary = false WHERE product_id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateResult = $updateStmt->execute([$productId]);
                error_log("Primary vendor update result: " . ($updateResult ? 'SUCCESS' : 'FAILED'));
            }

            // Simple insert approach
            $sql = "INSERT INTO product_vendors (
            product_id, partner_id, lead_time_days, cost_price, moq, is_primary, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);

            $params = [
                $productId,
                $partnerId,
                $data['lead_time_days'] ?? 0,
                $data['cost_price'] ?? 0,
                $data['moq'] ?? 1,
                $data['is_primary'] ?? false,
                $data['notes'] ?? null
            ];

            error_log("Executing SQL with params: " . print_r($params, true));

            $result = $stmt->execute($params);
            error_log("Execute result: " . ($result ? 'SUCCESS' : 'FAILED'));

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("SQL Error Info: " . print_r($errorInfo, true));

                // Jika duplicate, consider success
                if (isset($errorInfo[0]) && $errorInfo[0] === '23505') {
                    error_log("Duplicate entry - considering as success");
                    return true;
                }
            } else {
                $rowCount = $stmt->rowCount();
                error_log("Rows affected: " . $rowCount);
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Product::addVendor() PDO Exception: " . $e->getMessage());
            error_log("Error Code: " . $e->getCode());
            error_log("SQL State: " . $e->errorInfo[0] ?? 'unknown');

            // Handle duplicate entry
            if ($e->getCode() === '23505' || ($e->errorInfo[0] ?? '') === '23505') {
                error_log("Duplicate entry detected - returning success");
                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Product::addVendor() General Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove vendor from product
     */
    public static function removeVendor($vendorId)
    {
        try {
            $pdo = Database::getInstance();

            $sql = "DELETE FROM public.product_vendors WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$vendorId]);
        } catch (PDOException $e) {
            error_log("Product::removeVendor() Error: " . $e->getMessage());
            return false;
        }
    }

    public static function getChartOfAccounts($type = null)
    {
        return [];
    }

    public static function getAccountingInfo($productId)
    {
        return [
            'total_inventory_value' => 0,
            'average_cost' => 0,
            'last_purchase_price' => 0,
            'total_cogs' => 0,
            'total_revenue' => 0
        ];
    }

    public static function getProductTransactions($productId, $filters = [])
    {
        return [];
    }

    public static function saveProductAccounting($productId, $accountingData)
    {
        global $pdo;

        $sql = "UPDATE public.products SET 
                inventory_account_id = $1,
                cogs_account_id = $2, 
                income_account_id = $3,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = $4";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $accountingData['inventory_account'] ?: null,
            $accountingData['cogs_account'] ?: null,
            $accountingData['income_account'] ?: null,
            $productId
        ]);
    }
}
