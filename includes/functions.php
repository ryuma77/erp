<?php
function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function setFlashMessage($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlashMessage()
{
    $flash = getFlashMessage();
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        $alertClass = $type === 'success' ? 'alert-success' : ($type === 'error' ? 'alert-danger' : ($type === 'warning' ? 'alert-warning' : 'alert-info'));

        echo "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

function formatDate($date, $format = 'd M Y')
{
    return date($format, strtotime($date));
}

function formatCurrency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Form rendering helpers
function renderFormField($name, $label, $type = 'text', $value = '', $extra = '', $attributes = '')
{
    $html = '<div class="mb-3">';
    $html .= '<label for="' . $name . '" class="form-label">' . $label . '</label>';
    $html .= '<input type="' . $type . '" class="form-control" id="' . $name . '" name="' . $name . '" value="' . htmlspecialchars($value) . '" ' . $attributes . ' ' . $extra . '>';
    $html .= '</div>';
    return $html;
}

function renderSelectField($name, $label, $options = [], $selected = '', $required = false)
{
    $html = '<div class="mb-3">';
    $html .= '<label for="' . $name . '" class="form-label">' . $label . '</label>';
    $html .= '<select class="form-select" id="' . $name . '" name="' . $name . '" ' . ($required ? 'required' : '') . '>';
    $html .= '<option value="">-- Select ' . $label . ' --</option>';
    foreach ($options as $option) {
        $isSelected = ($selected == $option['id']) ? 'selected' : '';
        $html .= '<option value="' . $option['id'] . '" ' . $isSelected . '>' . htmlspecialchars($option['category_name'] ?? $option['unit_name'] ?? $option['name'] ?? '') . '</option>';
    }
    $html .= '</select></div>';
    return $html;
}

function renderTextareaField($name, $label, $value = '', $rows = 3)
{
    $html = '<div class="mb-3">';
    $html .= '<label for="' . $name . '" class="form-label">' . $label . '</label>';
    $html .= '<textarea class="form-control" id="' . $name . '" name="' . $name . '" rows="' . $rows . '">' . htmlspecialchars($value) . '</textarea>';
    $html .= '</div>';
    return $html;
}

function renderPriceField($name, $label, $value = '0')
{
    $html = '<div class="mb-3">';
    $html .= '<label for="' . $name . '" class="form-label">' . $label . '</label>';
    $html .= '<div class="input-group"><span class="input-group-text">Rp</span>';
    $html .= '<input type="number" class="form-control" id="' . $name . '" name="' . $name . '" value="' . $value . '" step="0.01" min="0" required>';
    $html .= '</div></div>';
    return $html;
}
