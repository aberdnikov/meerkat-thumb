<?php

    namespace Meerkat\Thumb;

    use Meerkat\Html\Img;
    use Meerkat\Slot\Slot_Thumb;
    use \Kohana as Kohana;
    use \Arr as Arr;
    use \Image as Image;
    use \Debug as Debug;
    use Meerkat\Widget\Widget_Alert;

    class Thumb {

        const RAW = 'raw';
        //        public $url;
        //        public $file;
        //        public $alt;
        //        public $w;
        //        public $h;
        protected $entity;
        protected $prop;
        protected $id;
        protected $config;

        function __construct($entity, $id, $prop = null) {
            $this->id     = intval($id);
            $this->entity = $entity;
            $this->prop   = $prop;
            $this->config = Kohana::$config
                ->load('meerkat/thumbs/' . $entity . ($prop ? '/' . $prop : ''));
            if (!count($this->config->as_array())) {
                //Debug::stop('meerkat/thumbs/' . $entity . ($prop ? '/' . $prop : ''));
                //throw new \HTTP_Exception_500('meerkat/thumbs/' . $entity . ($prop ? '/' . $prop : ''));
                throw new \HTTP_Exception_500('Do not set the settings in the configuration file thumbnails for "' . $entity . ($prop ? '/' . $prop : '') . '"');
            }
            if (!$this->entity) {
                throw new \HTTP_Exception_500('Do not set the name of the entity thumbnails');
            }
        }

        static function factory($entity, $id, $prop = null) {
            return new Thumb($entity, $id, $prop);
        }

        function rebuild() {
            try {
                $all = $this->get_all();
                //\Debug::info($all);
                $raw = Arr::path($all, self::RAW . '.file');
                //\Debug::stop($raw);
                $this->delete(false);
                if (file_exists($raw)) {
                    $this->make($raw);
                }
                return false;
            }
            catch (\Exception $exc) {
                return \Kohana_Exception::text($exc);
            }
        }

        function get_all() {
            $slot = Slot_Thumb::factory($this->slot_key());
            if (!($ret = $slot->get())) {
                $ret = array();
                foreach ($this->config->sizes as $size => $cfg) {
                    $w         = Arr::get($cfg, 'w');
                    $h         = Arr::get($cfg, 'h');
                    $file_path = $this->file_path($size);
                    $file_url  = $this->file_url($size);
                    if (is_file($file_path)) {
                        $ret[$size] = array(
                            'w'    => $w,
                            'h'    => $h,
                            'file' => $file_path,
                            'url'  => $file_url,
                        );
                    }
                }
                $slot->set($ret);
            }
            foreach ($this->config->sizes as $size => $cfg) {
                $w = Arr::get($cfg, 'w');
                $h = Arr::get($cfg, 'h');
                if (!isset($ret[$size])) {
                    $ret[$size] = array(
                        'w' => $w,
                        'h' => $h,
                    );
                    if ($default = Arr::get($cfg, 'default')) {
                        $ret[$size]['url'] = \Meerkat\StaticFiles\Helper::static_url($default);
                    }
                    else {
                        $ret[$size]['url'] = false;
                        //<img class="img-circle" data-src="holder.js/140x140" alt="Generic placeholder image">
                        //$ret[$size]['url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/placeholdit/' . $w . '/' . $h;
                        $ret[$size]['data-src'] = 'holder.js/' . $w . 'x' . $h;
                    }
                }
            }
            //Debug::stop($ret);
            return $ret;
        }

        function slot_key() {
            return $this->entity . '-' . $this->id . '-' . $this->prop;
        }

        function file_path($size) {
            $dir = '';
            if (isset($this->config->upload_dir)) {
                $dir = $this->config->upload_dir;
            }
            if (!$dir) {
                $dir = Kohana::$config->load('meerkat/thumbs.upload_dir');
            }
            return $dir . self::postfix($size);
        }

        protected function postfix($size) {
            return $this->entity
            . '/' . mb_substr($this->id, -1)
            . '/' . mb_substr($this->id, -2, 1)
            . '/' . $this->id . '.' . (($size && $size != Thumb::RAW) ? $size . '_' : '')
            . ($this->prop ? $this->prop . '_' : '')
            . md5($this->prop . $this->id . $size . Kohana::$config->load('meerkat/thumbs.salt')) . '.' . (isset($this->config->extension) ? $this->config->extension : 'jpeg');
        }

        function file_url($size = null) {
            $url = '';
            if (isset($this->config->upload_url)) {
                $url = $this->config->upload_url;
            }
            if (!$url) {
                $url = Kohana::$config->load('meerkat/thumbs.upload_url');
            }
            return $url . self::postfix($size);
        }

        function delete($with_raw = true) {
            foreach ($this->get_all() as $name => $thumb) {
                if ($name == self::RAW && !$with_raw) {
                    continue;
                }
                $file = Arr::get($thumb, 'file');
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            $slot = Slot_Thumb::factory($this->slot_key());
            $slot->remove();
        }

        function make($original) {
            try {
                if (filter_var($original, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
                    $img = \Request::factory($original)
                        ->execute();
                    umask(0);
                    $dir = DOMAINPATH . "!/tmp";
                    if (!file_exists($dir)) {
                        try {
                            mkdir($dir, 0777, true);
                        }
                        catch (\Exception $exc) {
                            return 'Is not possible to create a directory for temporary files DOMAINPATH . "!/tmp"';
                        }
                    }
                    $original = tempnam($dir, $this->entity);
                    $handle   = fopen($original, "w");
                    fwrite($handle, $img);
                    fclose($handle);
                }
                if (!file_exists($original)) {
                    throw new \HTTP_Exception_500('The source file is not found');
                }
                if (!getimagesize($original)) {
                    throw new \HTTP_Exception_500('The source file is not an image');
                }
                foreach ($this->config->sizes as $size => $cfg) {
                    $file_path = $this->file_path($size);
                    $dir       = dirname($file_path);
                    if (!file_exists($dir)) {
                        if (!mkdir($dir, 0777, true)) {
                            throw new \HTTP_Exception_500('Нет прав на создание директорий для изображений');
                        }
                    }
                    if ($size === Thumb::RAW) {
                        if(file_exists($file_path)){
                            if(!unlink($file_path)){
                                throw new \HTTP_Exception_500('Удаление оригинала не получилось');
                            }
                        }
                        if (!copy($original, $file_path)) {
                            throw new \HTTP_Exception_500('Копирование оригинала не получилось');
                        }
                    }
                    else {
                        $image = \Image::factory($original);
                        $w     = Arr::get($cfg, 'w', $image->width);
                        $h     = Arr::get($cfg, 'h', $image->height);
                        $m     = Arr::get($cfg, 'm', 'crop_img_in_box');
                        $image->$m($w, $h);
                        if (!$image->save($file_path)) {
                            throw new \HTTP_Exception_500('Копирование превью не получилось');
                        }
                        //Debug::info($cfg);
                        //Debug::info($file_path);
                    }
                    //chmod($file_path, 0777);
                }
                //Debug::stop($_FILES);
                $slot = Slot_Thumb::factory($this->slot_key());
                $slot->remove();
                return false;
            }
            catch (\Exception $exc) {
                \Meerkat\Widget\Widget_Alert::factory($exc->getMessage())
                    ->as_error()
                    ->put();
                return $exc->getMessage();
            }
        }

        function get_size_names() {
            return array_keys($this->config->sizes);
        }

        function get_sizes() {
            return array_keys($this->config);
        }

        function img($size, $attrs = null, $with_rand = false) {
            $img_src = $this->img_src($size, $with_rand);
            $img     = Img::factory();
            foreach ($img_src as $k => $v) {
                $img->set_attr($k, $v);
            }
            if ($attrs) {
                foreach ($attrs as $k => $v) {
                    $img->set_attr($k, $v);
                }
            }
            return $img;

        }

        function img_src($size, $with_rand = false) {
            $ret      = array();
            $thumb    = $this->get($size);
            $url      = \Arr::get($thumb, 'url');
            $data_scr = \Arr::get($thumb, 'data-src');
            if ($url) {
                $ret['src'] = $url . ($with_rand ? '?' . microtime(true) : '');
            }
            else {
                $ret['data-src'] = $data_scr;
                $ret['alt']      = 'Generic placeholder image';
            }
            return $ret;
        }

        /**
         *
         * @param type $size
         * @return Thumb
         */
        function get($size, $param = null) {
            $item = Arr::get($this->get_all(), $size);
            if ($param) {
                return Arr::get($item, $param);
            }
            return $item;
        }

    }