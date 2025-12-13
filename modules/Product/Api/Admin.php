<?php
/**
 * ========== 产品管理增强 by xkatld ==========
 * @link https://github.com/xkatld/FOSSBilling-Patch
 * @version v1.0.0
 */

namespace Box\Mod\Product\Api;

class Admin extends \Api_Abstract
{
    public function get_list($data)
    {
        $service = $this->getService();
        [$sql, $params] = $service->getProductSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $model = $this->di['db']->getExistingModelById('Product', $item['id'], 'Post not found');
            $pager['list'][$key] = $this->getService()->toApiArray($model, false, $this->getIdentity());
        }
        return $pager;
    }

    public function get_pairs($data)
    {
        $service = $this->getService();
        return $service->getPairs($data);
    }

    public function get($data)
    {
        $model = $this->_getProduct($data);
        $service = $this->getService();
        return $service->toApiArray($model, true, $this->getIdentity());
    }

    public function get_types()
    {
        return $this->getService()->getTypes();
    }

    public function prepare($data)
    {
        $required = [
            'title' => 'You must specify a title',
            'type' => 'Type is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $service = $this->getService();
        if ($data['type'] == 'domain') {
            $model = $service->getMainDomainProduct();
            if ($model instanceof \Model_Product) {
                throw new \FOSSBilling\InformationException('You have already created domain product.', null, 413);
            }
        }

        $types = $service->getTypes();
        if (!array_key_exists($data['type'], $types)) {
            throw new \FOSSBilling\Exception('Product type :type is not registered.', [':type' => $data['type']], 413);
        }

        $categoryId = $data['product_category_id'] ?? null;
        return (int) $service->createProduct($data['title'], $data['type'], $categoryId);
    }

    public function update($data)
    {
        $model = $this->_getProduct($data);
        $service = $this->getService();
        return $service->updateProduct($model, $data);
    }

    public function update_priority($data)
    {
        if (!isset($data['priority']) || !is_array($data['priority'])) {
            throw new \FOSSBilling\Exception('priority params is missing');
        }
        $service = $this->getService();
        return $service->updatePriority($data);
    }

    public function update_config($data)
    {
        $model = $this->_getProduct($data);
        $service = $this->getService();
        return $service->updateConfig($model, $data);
    }

    public function addon_get_pairs($data)
    {
        return $this->getService()->getAddons();
    }

    public function addon_create($data)
    {
        $required = [
            'title' => 'You must specify a title',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $title = $data['title'];
        $status = $data['status'] ?? null;
        $setup = $data['setup'] ?? null;
        $iconUrl = $data['icon_url'] ?? null;
        $description = $data['description'] ?? null;

        $service = $this->getService();
        return $service->createAddon($title, $description, $setup, $status, $iconUrl);
    }

    public function addon_get($data)
    {
        $required = [
            'id' => 'Addon ID is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->load('Product', $data['id']);
        if (!$model instanceof \Model_Product || !$model->is_addon) {
            throw new \FOSSBilling\Exception('Addon not found');
        }
        $service = $this->getService();
        return $service->toApiArray($model, true, $this->getIdentity());
    }

    public function addon_update($data)
    {
        $required = [
            'id' => 'Addon ID is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->load('Product', $data['id']);
        if (!$model instanceof \Model_Product || !$model->is_addon) {
            throw new \FOSSBilling\Exception('Addon not found');
        }
        $this->di['logger']->info('Updated addon #%s', $model->id);
        return $this->update($data);
    }

    public function addon_delete($data)
    {
        return $this->delete($data);
    }

    public function delete($data)
    {
        $model = $this->_getProduct($data);
        $service = $this->getService();
        return $service->deleteProduct($model);
    }

    public function copy($data)
    {
        $model = $this->_getProduct($data);
        $service = $this->getService();
        return $service->copyProduct($model);
    }

    public function category_get_pairs($data)
    {
        return $this->getService()->getProductCategoryPairs($data);
    }

    public function category_update($data)
    {
        $required = [
            'id' => 'Category ID is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('ProductCategory', $data['id'], 'Category not found');

        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $icon_url = $data['icon_url'] ?? null;

        $service = $this->getService();
        return $service->updateCategory($model, $title, $description, $icon_url);
    }

    public function category_get($data)
    {
        $required = [
            'id' => 'Category ID is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('ProductCategory', $data['id'], 'Category not found');
        return $this->getService()->toProductCategoryApiArray($model);
    }

    public function category_create($data)
    {
        $required = [
            'title' => 'Category title is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $service = $this->getService();
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $icon_url = $data['icon_url'] ?? null;

        return (int) $service->createCategory($title, $description, $icon_url);
    }

    public function category_delete($data)
    {
        $required = [
            'id' => 'Category ID is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('ProductCategory', $data['id'], 'Category not found');
        $service = $this->getService();
        return $service->removeProductCategory($model);
    }

    public function promo_get_list($data)
    {
        $service = $this->getService();
        [$sql, $params] = $service->getPromoSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $model = $this->di['db']->getExistingModelById('Promo', $item['id'], 'Promo not found');
            $pager['list'][$key] = $this->getService()->toPromoApiArray($model);
        }
        return $pager;
    }

    public function promo_create($data)
    {
        $required = [
            'code' => 'Promo code is missing',
            'type' => 'Promo type is missing',
            'value' => 'Promo value is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $products = [];
        if (isset($data['products']) && is_array($data['products'])) {
            $products = $data['products'];
        }
        $periods = [];
        if (isset($data['periods']) && is_array($data['periods'])) {
            $periods = $data['periods'];
        }
        $clientGroups = [];
        if (isset($data['client_groups']) && is_array($data['client_groups'])) {
            $clientGroups = $data['client_groups'];
        }
        $service = $this->getService();
        return (int) $service->createPromo($data['code'], $data['type'], $data['value'], $products, $periods, $clientGroups, $data);
    }

    public function promo_get($data)
    {
        $required = [
            'id' => 'Promo ID is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Promo', $data['id'], 'Promo not found');
        return $this->getService()->toPromoApiArray($model, true, $this->getIdentity());
    }

    public function promo_update($data)
    {
        $required = [
            'id' => 'Promo ID is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Promo', $data['id'], 'Promo not found');
        $service = $this->getService();
        return $service->updatePromo($model, $data);
    }

    public function promo_delete($data)
    {
        $required = [
            'id' => 'Promo ID is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Promo', $data['id'], 'Promo not found');
        return $this->getService()->deletePromo($model);
    }

    public function get_products_by_category()
    {
        return $this->getService()->getProductsByCategory();
    }

    private function _getProduct($data)
    {
        $required = [
            'id' => 'Product ID not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        return $this->di['db']->getExistingModelById('Product', $data['id'], 'Product not found');
    }
}
