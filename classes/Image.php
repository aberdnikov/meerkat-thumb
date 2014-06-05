<?php
    abstract class Image extends \Kohana_Image {


        /**
         * Вписываем изображение в указанную ширину
         * Высота какая получится такая и будет
         * Пример: фотки на аватарках в контактике
         * @param type $w
         * @return type
         */
        function resize_by_w($w) {
            return $this->resize($w, null);
        }

        /**
         * Насильно вписываем изображение без учета пропорций в указанные рамки
         * @param type $w
         * @param type $h
         * @return type
         */
        function resize_ignoring_aspect_ratio($w, $h) {
            return $this->resize($w, $h, Image::NONE);
        }

        /**
         * Уменьшаем размер исходного изображения с сохранением пропорций так,
         * чтобы новое получилось вписанным в указанный размер
         * Там где изображение уже отсутствует - добиваем белым цветом до указанного размера
         * @param type $width
         * @param type $height
         * @return \Image
         */
        function crop_img_in_box($width, $height) {
            //сделаем так, чтобы исходная картинка вписывалась большей стороной в указанный прямоугольник
            $this->resize_img_in_box($width, $height);
            //рассчитаем отступы
            $offset_x = abs(round(($this->width - $width) / 2));
            $offset_y = abs(round(($this->height - $height) / 2));
            //подготовим фон залитый белым цветом
            $final           = imagecreatetruecolor($width, $height);
            $backgroundColor = imagecolorallocate($final, 255, 255, 255);
            imagefill($final, 0, 0, $backgroundColor);
            //скопируем в него исходнеое уменьшенное изображение
            if (imagecopy($final, $this->_image, $offset_x, $offset_y, 0, 0, $this->width, $this->height)) {
                // Swap the new image for the old one
                imagedestroy($this->_image);
                $this->_image = $final;
                // Reset the width and height
                $this->width  = imagesx($final);
                $this->height = imagesy($final);
            }
            return $this;
        }

        /**
         * Исходная картинка сжимается до тех пор пока не начнет целиком входить
         * в указанные рамки
         * С сохранением пропорций
         * @param type $w
         * @param type $h
         * @return type
         */
        function resize_img_in_box($w, $h) {
            return $this->resize($w, $h);
        }

        function crop_box_in_img($width, $height) {
            //сделаем так, чтобы исходная картинка вписывалась большей стороной в указанный прямоугольник
            $this->resize_box_in_img($width, $height);
            //рассчитаем отступы
            $offset_x = (round(($width - $this->width) / 2));
            $offset_y = (round(($height - $this->height) / 2));
            //подготовим фон залитый белым цветом
            $final           = imagecreatetruecolor($width, $height);
            $backgroundColor = imagecolorallocate($final, 255, 255, 255);
            imagefill($final, 0, 0, $backgroundColor);
            //скопируем в него исходнеое уменьшенное изображение
            if (imagecopy($final, $this->_image, $offset_x, $offset_y, 0, 0, $this->width, $this->height)) {
                // Swap the new image for the old one
                imagedestroy($this->_image);
                $this->_image = $final;
                // Reset the width and height
                $this->width  = imagesx($final);
                $this->height = imagesy($final);
            }
            return $this;
        }

        /**
         * Указанная рамка должна помещаться внутрь конечного изображения
         * Т.е. если заказываем 100 на 400 а картинка 2000 на 1000
         * То картинка будет уменьшаться до тех пор пока ее высота меньше указанного
         * или ширина меньше указанного
         * @param type $w
         * @param type $h
         * @return type
         */
        function resize_box_in_img($w, $h) {
            return $this->resize($w, $h, Image::INVERSE);
        }

    }