<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    DevBlinders <info@devblinders.com>
*  @copyright 2007-2020 DevBlinders
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/


class AdminDbBlogPostController extends ModuleAdminController
{

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'dbblog_post';
        $this->className = 'DbBlogPost';
        $this->lang = true;
        //$this->multishop_context = Shop::CONTEXT_ALL;

        parent::__construct();

        $this->fields_list = array(
            'id_dbblog_post' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'align' => 'center',
                'width' => 30
            ),
            'image' => array(
                'title' => $this->trans('Imagen', array(), 'Admin.Global'),
                'width' => 150,
                'orderby' => false,
                'search' => false,
                'callback' => 'getImg',
            ),
            'title' => array(
                'title' => $this->trans('Nombre', array(), 'Admin.Global'),
            ),
            'short_desc' => array(
                'title' => $this->trans('Descripción', array(), 'Admin.Global'),
                'callback' => 'cleanHtml',
                'width' => 500,
            ),
            'featured' => array(
                'title' => 'Destacado',
                'active' => 'featured',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'ajax' => true,
                'orderby' => false,
                'search' => true,
                'width' => 25,
            ),
            'active' => array(
                'title' => 'Activo',
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'ajax' => true,
                'orderby' => false,
                'search' => true,
                'width' => 25,
            ),
        );

        if($this->module->premium == 1) {
            $this->fields_list['index'] = DbBlogPremium::renderListProduct();
        }

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
    }

    public function initProcess()
    {
        if (Tools::getIsset('status'.$this->table))
        {
            DbBlogPost::isToggleStatus((int)Tools::getValue('id_dbblog_post'));
            return;
        }

        if (Tools::getIsset('index'.$this->table))
        {
            DbBlogPost::isToggleIndex((int)Tools::getValue('id_dbblog_post'));
            return;
        }

        if (Tools::getIsset('featured'.$this->table))
        {
            DbBlogPost::isToggleFeatured((int)Tools::getValue('id_dbblog_post'));
            return;
        }

        return parent::initProcess();
    }

    public function renderList()
    {
        // Agregar el campo en la selección de la consulta SQL
        $this->_select .= ', p.`publish_date`, p.`update_date`';
    
        // Obtener la lista de elementos
        $list = parent::renderList();
    
        // Formatear las fechas y agregarlas a cada elemento en la lista
        foreach ($list as &$listItem) {
            $listItem['publish_date'] = Tools::displayDate($listItem['publish_date'], (int)$this->context->language->id);
            $listItem['update_date'] = Tools::displayDate($listItem['update_date'], (int)$this->context->language->id);
        }
    
        // Definir las columnas de fechas en la lista
        $this->fields_list['publish_date'] = [
            'title' => $this->l('Publish Date'),
            'align' => 'center',
            'type' => 'datetime',
            'orderby' => 'p!publish_date',
        ];
    
        $this->fields_list['update_date'] = [
            'title' => $this->l('Update Date'),
            'align' => 'center',
            'type' => 'datetime',
            'orderby' => 'p!update_date',
        ];
    
        // Remover enlaces en las filas
        $this->list_no_link = true;
    
        // Aplicar filtro de tienda si es necesario
        if (Shop::getContext() == Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            $this->_where = ' AND b.`id_shop` = '.(int)Context::getContext()->shop->id;
        }
    
        // Agregar acciones en las filas
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    
        return $list;
    }

    public function renderView()
    {
        // gets necessary objects
        $id_dbblog_post = (int)Tools::getValue('id_dbblog_post');
        return parent::renderView();
    }

    public function renderForm()
{
    // Carga el objeto si es una edición
    $obj = $this->loadObject(true);

    // Configura el título de la barra de herramientas
    if (Tools::isSubmit('add'.$this->table)) {
        $this->toolbar_title = $this->l('Crear nuevo post');
    } else {
        $this->toolbar_title = $this->l('Actualizar post');
    }

    // Obtiene las categorías y autores
    $categories = DbBlogCategory::getCategories($this->context->language->id, true, -1);
    $categories_selected = DbBlogCategory::getCategoriesSelected($obj->id_dbblog_post);
    $authors = DbBlogPost::getAuthors(999);

    // Define la imagen si existe
    $image = '';
    if (isset($obj->id)) {
        // ... (código existente para manejo de imagen)
    }

    // Define los campos del formulario
    $this->fields_form = array(
        'legend' => array(
            'title' => $this->l('Post'),
            'icon' => 'icon-pencil'
        ),
        'input' => array(
            array(
                'type' => 'hidden',
                'name' => 'id_dbblog_category',
            ),
            array(
                'type' => 'select',
                'label' => $this->l('Categoría'),
                'name' => 'category_post',
                'multiple' => true,
                'required' => true,
                'desc' => $this->l('Selecciona todas las categorias donde quieres que aparezca el post'),
                'options' => array(
                    'id' => 'id',
                    'query' => $categories,
                    'name' => 'title'
                )
            ),
            // ... (código existente para otros campos)

            array(
                'type' => 'datetime',
                'label' => $this->l('Publish Date'),
                'name' => 'publish_date',
                'lang' => false,
            ),
            array(
                'type' => 'datetime',
                'label' => $this->l('Update Date'),
                'name' => 'update_date',
                'lang' => false,
            ),
        ),
    );

    if ($this->module->premium == 1) {
        // Agregar campos adicionales si el módulo es premium
        $this->fields_form['input'][] = DbBlogPremium::renderFormProduct();
    }

    // Agregar el botón de guardar y permanecer
    $this->fields_form['submit'] = array(
        'title' => $this->trans('Save', array(), 'Admin.Actions'),
    );

    $this->fields_form['buttons'] = array(
        'save-and-stay' => array(
            'title' => $this->trans('Guardar y permanecer', array(), 'Admin.Actions'),
            'name' => 'submitAdd'.$this->table.'AndStay',
            'type' => 'submit',
            'class' => 'btn btn-default pull-right',
            'icon' => 'process-icon-save'
        )
    );

    return parent::renderForm();
}

public function postProcess()
{
    if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
        // Cargue el objeto (publicación) si existe, o cree uno nuevo
        $object = $this->loadObject(true);

        // Actualice las propiedades de publicación_fecha y actualización_fecha
        $object->publish_date = Tools::getValue('publish_date');
        $object->update_date = Tools::getValue('update_date');

        // Validar y guardar el objeto.
        if ($object->validateFields() && $object->update()) {
            $this->redirect_after = self::$currentIndex.'&'.$this->identifier.'='.$this->object->id.'&update'.$this->table.'&conf=4&token='.$this->token;
        } else {
            $this->errors[] = $this->l('An error occurred while updating the object.');
        }
    } else {
        parent::postProcess();
    }
}



    public function processAdd()
    {
        $object = parent::processAdd();

        if ($object->id > 0) {
            // Imagen
            if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                $image_name = $this->saveImg($object);
                $object->image = $image_name;
                $object->update();
            }

            // Categorias Asociadas
            if (!empty(Tools::getValue('category_post')) && count(Tools::getValue('category_post')) > 0) {
                foreach (Tools::getValue('category_post') as $id_category) {
                    $id_post = $object->id;
                    $sql = "INSERT INTO " . _DB_PREFIX_ . "dbblog_category_post VALUES ('$id_category', '$id_post')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($sql);
                }
            }
        }

        return $object;
    }

    public function processUpdate()
    {
        $object = parent::processUpdate();

        if ($object != false && $object->id_dbblog_post > 0) {
            // Imagen
            $image_name = $this->saveImg($object);
            $object->image = $image_name;
            $object->update();

            // Categorias Asociadas
            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute("DELETE FROM " . _DB_PREFIX_ . "dbblog_category_post WHERE id_dbblog_post = '" . $object->id_dbblog_post . "'");
            if (is_array(Tools::getValue('category_post'))) {
                foreach (Tools::getValue('category_post') as $id_category) {
                    $id_post = $object->id_dbblog_post;
                    $sql = "INSERT INTO " . _DB_PREFIX_ . "dbblog_category_post VALUES ('$id_category', '$id_post')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($sql);
                }
            }
        }

        return $object;
    }

    public function processDelete()
    {
        $object = parent::processDelete();

        // Delete Image
        if(isset($object->image)) {
            foreach ($object->image as $image) {
                if(!empty($image)) {
                    $del_img = _PS_MODULE_DIR_ . 'dbblog/views/img/post/' . $image;
                    if (file_exists($del_img)) {
                        unlink($del_img);
                    }
                }
            }
        }

        return $object;
    }

    public function cleanHtml($html)
    {
        return strip_tags(stripslashes($html));
    }

    public function saveImg($post)
    {
        // Guardamos las imagenes
        $type = Tools::strtolower(Tools::substr(strrchr($_FILES['image']['name'], '.'), 1));
        $imagesize = @filesize($_FILES['image']['tmp_name']);
        if (isset($_FILES['image']) &&
            isset($_FILES['image']['tmp_name']) &&
            !empty($_FILES['image']['tmp_name']) &&
            !empty($imagesize) &&
            in_array($type, array('jpg', 'gif', 'jpeg', 'png', 'webp'))
        ) {
            $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
            $imagen_tmp = $_FILES['image']['tmp_name'];
            $image_name = $post->id.'-'.$post->link_rewrite[1];
            $image_name_extension = $post->id.'-'.$post->link_rewrite[1].'.'.$type;
            $dir_img = dirname(__FILE__).'/../../views/img/post/';
            if (!move_uploaded_file($imagen_tmp, $dir_img.$image_name_extension)) {
                $this->errors[] = $this->l('Error al subir la imagen');
                return false;
            }

            // redimensionamos las imagenes
            $img_orig = $dir_img.$image_name_extension;
            $img_small = $dir_img.$image_name.'-small.'.$type;
            $img_big = $dir_img.$image_name.'-big.'.$type;
            list($originalWidth, $originalHeight) = getimagesize($img_orig);
            $ratio = $originalWidth / $originalHeight;
            $height_small = 400 / $ratio;
            $height_big = 800 / $ratio;
            ImageManager::resize($img_orig, $img_small, 400, $height_small);
            ImageManager::resize($img_orig, $img_big, 800, $height_big);

            // Generamos el webp
            $checkWebp = $this->module->checkWebp();
            if($checkWebp && $type != 'webp') {
                $img_small_webp = $img_small.'.webp';
                $img_big_webp = $img_big.'.webp';
                DbBlogPremium::convertImageToWebP($img_small, $img_small_webp);
                DbBlogPremium::convertImageToWebP($img_big, $img_big_webp);
            }

            if (isset($temp_name)) {
                @unlink($temp_name);
            }

        } else {
            $image_name_extension = Tools::getValue('image_old');
        }

        return $image_name_extension;
    }

    public static function getImg($img, $row)
    {
        if (file_exists(_PS_MODULE_DIR_ . 'dbblog/views/img/post/'.$img) && !empty($img)) {
            $image = ImageManager::thumbnail(_PS_MODULE_DIR_ . 'dbblog/views/img/post/'.$img, 'dbblog_'.$img, 75, 'jpg', true);
            return $image;
        } else {
            return;
        }
    }

}