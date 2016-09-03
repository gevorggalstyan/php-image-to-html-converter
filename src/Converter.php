<?php

namespace GevorgGalstyan\Image2HTML;

class Converter
{
    private $path;
    private $color_type;
    private $width;
    private $pixel_size;
    private $blur;
    private $true_color;
    private $type;
    private $image;
    private $converted_html;

    const HEXA = 0;
    const RGBA = 1;
    const BEST = 2;

    public function __construct($file_name,
                                $color_type = Converter::BEST,
                                $width = 100,
                                $pixel_size = 1,
                                $blur = 0,
                                $true_color = TRUE)
    {
        $this->set_path($file_name);
        $this->set_color_type($color_type);
        $this->set_width($width);
        $this->set_pixel_size($pixel_size);
        $this->set_blur($blur);
        $this->set_true_color($true_color);
    }

    public function set_path($path)
    {
        $this->path = $path;
    }

    public function get_path()
    {
        return $this->path;
    }

    public function set_color_type($type)
    {
        if (
            $type !== Converter::HEXA
            && $type !== Converter::RGBA
            && $type !== Converter::BEST
        ) {
            throw new \InvalidArgumentException('Color type not allowed.');
        }
        $this->color_type = $type;
    }

    public function set_width($width)
    {
        $this->width = $width;
    }

    public function get_width()
    {
        return $this->width;
    }

    public function set_pixel_size($size)
    {
        $this->pixel_size = $size;
    }

    public function get_pixel_size()
    {
        return $this->pixel_size;
    }

    public function set_blur($blur)
    {
        $this->blur = $blur;
    }

    public function get_blur()
    {
        return $this->blur;
    }

    public function set_true_color($true_color)
    {
        $this->true_color = $true_color;
    }

    public function get_true_color()
    {
        return $this->true_color;
    }

    public function load($file_name)
    {
        $image_info = @getimagesize($file_name);

        if (!$image_info) {
            throw new \InvalidArgumentException('File is invalid image.');
        }

        $this->type = $image_info[2];

        if ($this->type !== IMAGETYPE_JPEG && $this->type !== IMAGETYPE_PNG) {
            throw new \InvalidArgumentException('Image type not allowed.');
        }

        if ($this->type === IMAGETYPE_PNG) {
            $this->image = imagecreatefrompng($file_name);
        } else {
            $this->image = imagecreatefromjpeg($file_name);
        }
    }

    public function get_image_width()
    {
        return imagesx($this->image);
    }

    public function get_image_height()
    {
        return imagesy($this->image);
    }

    public function resize($width)
    {
        $ratio = $width / $this->get_image_width();
        $height = $this->get_image_height() * $ratio;

        if ($this->true_color) {
            $img = imagecreatetruecolor($width, $height);
        } else {
            $img = imagecreate($width, $height);
        }

        imagealphablending($img, FALSE);
        imagesavealpha($img, TRUE);
        $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $transparent);
        imagecopyresampled($img, $this->image, 0, 0, 0, 0,
            $width, $height,
            $this->get_image_width(), $this->get_image_height());
        $this->image = $img;
    }

    public function get_color_map()
    {
        $colors = [];
        $image_width = $this->get_image_width();
        $image_height = $this->get_image_height();
        for ($h = 0; $h < $image_height; $h++) {
            $colors[$h] = [];
            for ($w = 0; $w < $image_width; $w++) {
                $a = imagecolorat($this->image, $w, $h);
                $b = imagecolorsforindex($this->image, $a);
                $colors[$h][$w] = $b;
            }
        }
        return $colors;
    }

    public function compute()
    {
        $this->load($this->get_path());
        $this->resize($this->get_width());

        $step = $this->get_pixel_size();
        $pixels = $this->get_color_map();

        $style = 'width:0; height:0; ' . PHP_EOL;
        $style .= '    box-shadow:' . PHP_EOL;

        $html = '<table cellpadding="0" cellspacing="0" ' .
            'style="padding: 0; margin: 0; border: 0;">';
        $html .= '  <tbody>';

        $cell_style = 'width:1px; height:1px; padding:0; margin:0;';

        foreach ($pixels as $row => $cols) {
            $html .= '<tr>';
            foreach ($cols as $col => $colors) {
                $alpha = round(($colors['alpha'] / -127) + 1, 1);
                if ($alpha) {
                    $style .= sprintf('%4s', $col * $step) . 'px ';
                    $style .= sprintf('%2s', $row * $step) . 'px ';
                    $style .= $this->get_blur()
                        ? $this->get_blur() . 'px '
                        : '0 ';
                    $style .= $step . 'px ';

                    if (($this->color_type === Converter::BEST && $alpha < 1)
                        OR $this->color_type === Converter::RGBA
                    ) {
                        $rgba = $colors['red'] . ',' .
                            $colors['green'] . ',' .
                            $colors['blue'] . ',' .
                            $alpha;
                        $style .= 'rgba(' . $rgba . ')';
                        $html .= '<td style="' . $cell_style .
                            'background-color:rgba(' . $rgba . ');"></td>';
                    } else {
                        $hex = '#' . $this->rgb_to_hex($colors['red']) .
                            $this->rgb_to_hex($colors['green']) .
                            $this->rgb_to_hex($colors['blue']);
                        $style .= strtoupper($hex);
                        $html .= '<td style="' . $cell_style .
                            'background-color:' . strtoupper($hex) . ';"></td>';
                    }
                    $style .= ',' . PHP_EOL;
                } else {
                    $html .= '<td style="' . $cell_style . '"></td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '  </tbody>';
        $html .= '</table>';

        $result = new \stdClass();
        $result->style = preg_replace('/,$/', ';', $style);
        $result->table = preg_replace('/,$/', ';', $html);

        $this->converted_html = $result;
    }

    private function rgb_to_hex($value)
    {
        return str_pad(dechex($value), 2, '0', STR_PAD_LEFT);
    }

    public function __toString()
    {
        return (string)$this->converted_html->table;
    }
}
