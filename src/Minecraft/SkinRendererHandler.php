<?php
namespace Cyndaron\Minecraft;

use Cyndaron\DBConnection;
use Cyndaron\Request;

/* ***** MINECRAFT 3D Skin Generator *****
 * The contents of this project were first developed by Pierre Gros on 17th April 2012.
 * It has once been modified by Carlos Ferreira (http://www.carlosferreira.me) on 31st May 2014.
 * Translations done by Carlos Ferreira.
 *
 **** GET Parameters ****
 ** These parameters have been renamed to match their English translations **
 * user - Minecraft's username for the skin to be rendered.
 * vr - Vertical Rotation.
 * hr - Horizontal Rotation.
 *
 * hrh - Horizontal Rotation of the Head.
 *
 * vrll - Vertical Rotation of the Left Leg.
 * vrrl - Vertical Rotation of the Right Leg.
 * vrla - Vertical Rotation of the Left Arm.
 * vrra - Vertical Rotation of the Right Arm.
 *
 * displayHair - Either or not to display hairs. Set to "false" to NOT display hairs.
 *
 * format - The format in which the image is to be rendered. PNG ("png") is used by default set to "svg" to use a vector version.
 * ratio - The size of the "png" image. The default and minimum value is 2.
 */

class SkinRendererHandler
{
    public static float $cos_alpha;
    public static float $sin_alpha;
    public static float $cos_omega;
    public static float $sin_omega;
    public static int $minX = 0;
    public static int $maxX = 0;
    public static int $minY = 0;
    public static int $maxY = 0;

    public function __construct()
    {
        $times = [];

        $times[] = ['Start', $this->microtime_float()];

        $username = Request::get('user');
        $userInfo = DBConnection::doQueryAndFetchFirstRow('SELECT  * FROM minecraft_members WHERE userName = ?', [$username]);
        $img_png = SkinRenderer::getSkinOrFallback($userInfo['skinUrl']);

        $width = imagesx($img_png);
        $height = imagesy($img_png);
        if ($height == $width)
        {
            $img_png_old = $img_png;
            $img_png = imagecreatetruecolor($width, $height / 2);
            imagecopyresampled($img_png, $img_png_old, 0, 0, 0, 0, $width, $height / 2, $width, $height / 2);
            $height = imagesy($img_png);
        }
        elseif (!($width == $height * 2) || $height % 32 != 0)
        {
            // Bad ratio created
            $img_png = imagecreatefrompng(SkinRenderer::FALLBACK_IMAGE);
        }

        $hd_ratio = $height / 32; // Set HD ratio to 2 if the skin is 128x64

        $times[] = ['Download-Image', $this->microtime_float()];

        $vertical_rotation = Request::get('vr');
        $horizontal_rotation = Request::get('hr');
        $display_hair = $userInfo['renderAvatarHair'];

        // Rotation variables in radians (3D Rendering)
        $alpha = deg2rad($vertical_rotation); // Vertical rotation on the X axis.
        $omega = deg2rad($horizontal_rotation); // Horizontal rotation on the Y axis.

        // Cosine and Sine values
        static::$cos_alpha = cos($alpha);
        static::$sin_alpha = sin($alpha);
        static::$cos_omega = cos($omega);
        static::$sin_omega = sin($omega);

        // Head, Helmet, Torso, Arms, Legs
        $parts_angles = [];
        $parts_angles['torso'] = [
            'cos_alpha' => cos(0),
            'sin_alpha' => sin(0),
            'cos_omega' => cos(0),
            'sin_omega' => sin(0),
        ];
        $alpha_head = 0;
        $omega_head = deg2rad(floatval(Request::get('hrh')));
        $parts_angles['head'] = [
            'cos_alpha' => cos($alpha_head),
            'sin_alpha' => sin($alpha_head),
            'cos_omega' => cos($omega_head),
            'sin_omega' => sin($omega_head),
        ];
        $parts_angles['helmet'] = [
            'cos_alpha' => cos($alpha_head),
            'sin_alpha' => sin($alpha_head),
            'cos_omega' => cos($omega_head),
            'sin_omega' => sin($omega_head),
        ];
        $alpha_right_arm = deg2rad(floatval(Request::get('vrra')));
        $omega_right_arm = 0;
        $parts_angles['rightArm'] = [
            'cos_alpha' => cos($alpha_right_arm),
            'sin_alpha' => sin($alpha_right_arm),
            'cos_omega' => cos($omega_right_arm),
            'sin_omega' => sin($omega_right_arm),
        ];
        $alpha_left_arm = deg2rad(floatval(Request::get('vrla')));
        $omega_left_arm = 0;
        $parts_angles['leftArm'] = [
            'cos_alpha' => cos($alpha_left_arm),
            'sin_alpha' => sin($alpha_left_arm),
            'cos_omega' => cos($omega_left_arm),
            'sin_omega' => sin($omega_left_arm),
        ];
        $alpha_right_leg = deg2rad(floatval(Request::get('vrrl')));
        $omega_right_leg = 0;
        $parts_angles['rightLeg'] = [
            'cos_alpha' => cos($alpha_right_leg),
            'sin_alpha' => sin($alpha_right_leg),
            'cos_omega' => cos($omega_right_leg),
            'sin_omega' => sin($omega_right_leg),
        ];
        $alpha_left_leg = deg2rad(floatval(Request::get('vrll')));
        $omega_left_leg = 0;
        $parts_angles['leftLeg'] = [
            'cos_alpha' => cos($alpha_left_leg),
            'sin_alpha' => sin($alpha_left_leg),
            'cos_omega' => cos($omega_left_leg),
            'sin_omega' => sin($omega_left_leg),
        ];


        $times[] = ['Angle-Calculations', $this->microtime_float()];

        $visible_faces_format = [
            'front' => [],
            'back' => [],
        ];

        $visible_faces = [
            'head' => $visible_faces_format,
            'torso' => $visible_faces_format,
            'rightArm' => $visible_faces_format,
            'leftArm' => $visible_faces_format,
            'rightLeg' => $visible_faces_format,
            'leftLeg' => $visible_faces_format,
        ];

        $all_faces = [
            'back',
            'right',
            'top',
            'front',
            'left',
            'bottom',
        ];

        // Loop each preProject and Project then calculate the visible faces for each - also display
        foreach ($visible_faces as $faceName => &$faceFormat)
        {
            unset($cube_max_depth_faces, $cubePoints);

            $cubePoints = $this->generateCubePoints();
            $cube_max_depth_faces = $cubePoints[0];

            foreach ($cubePoints as $cubePoint)
            {
                $cubePoint->getPoint()->preProject(0, 0, 0, $parts_angles[$faceName]['cos_alpha'], $parts_angles[$faceName]['sin_alpha'], $parts_angles[$faceName]['cos_omega'], $parts_angles[$faceName]['sin_omega']);
                $cubePoint->getPoint()->project();

                if ($cube_max_depth_faces->getPoint()->getDepth() > $cubePoint->getPoint()->getDepth())
                {
                    $cube_max_depth_faces = $cubePoint;
                }
            }
            $faceFormat['back'] = $cube_max_depth_faces->getPlaces();
            $faceFormat['front'] = array_diff($all_faces, $faceFormat['back']);
        }

        unset($cube_max_depth_faces);
        $cubePoints = $this->generateCubePoints();
        $cube_max_depth_faces = $cubePoints[0];

        /** @var CubePoint $cubePoint */
        foreach ($cubePoints as $cubePoint)
        {
            $cubePoint->getPoint()->project();
            if ($cube_max_depth_faces->getPoint()->getDepth() > $cubePoint->getPoint()->getDepth())
            {
                $cube_max_depth_faces = $cubePoint;
            }

        }
        $back_faces = $cube_max_depth_faces->getPlaces();
        $front_faces = array_diff($all_faces, $back_faces);

        $times[] = ['Determination-of-faces', $this->microtime_float()];

        $cube_faces_array = [
            'front' => [],
            'back' => [],
            'top' => [],
            'bottom' => [],
            'right' => [],
            'left' => [],
        ];
        $polygons = [
            'helmet' => $cube_faces_array,
            'head' => $cube_faces_array,
            'torso' => $cube_faces_array,
            'rightArm' => $cube_faces_array,
            'leftArm' => $cube_faces_array,
            'rightLeg' => $cube_faces_array,
            'leftLeg' => $cube_faces_array,
        ];
        $volume_points = [];

        // HEAD
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 9 * $hd_ratio; $j++)
            {
                if (!isset($volume_points[$i][$j][-2 * $hd_ratio]))
                {
                    $volume_points[$i][$j][-2 * $hd_ratio] = new Point(['x' => $i, 'y' => $j, 'z' => -2 * $hd_ratio]);
                }
                if (!isset($volume_points[$i][$j][6 * $hd_ratio]))
                {
                    $volume_points[$i][$j][6 * $hd_ratio] = new Point(['x' => $i, 'y' => $j, 'z' => 6 * $hd_ratio]);
                }
            }
        }
        for ($j = 0; $j < 9 * $hd_ratio; $j++)
        {
            for ($faceName = -2 * $hd_ratio; $faceName < 7 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[0][$j][$faceName]))
                {
                    $volume_points[0][$j][$faceName] = new Point(['x' => 0, 'y' => $j, 'z' => $faceName]);
                }
                if (!isset($volume_points[8 * $hd_ratio][$j][$faceName]))
                {
                    $volume_points[8 * $hd_ratio][$j][$faceName] = new Point(['x' => 8 * $hd_ratio, 'y' => $j, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($faceName = -2 * $hd_ratio; $faceName < 7 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[$i][0][$faceName]))
                {
                    $volume_points[$i][0][$faceName] = new Point(['x' => $i, 'y' => 0, 'z' => $faceName]);
                }
                if (!isset($volume_points[$i][8 * $hd_ratio][$faceName]))
                {
                    $volume_points[$i][8 * $hd_ratio][$faceName] = new Point(['x' => $i, 'y' => 8 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 8 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 8 * $hd_ratio; $j++)
            {
                $polygons['head']['back'][] = new Polygon([
                    $volume_points[$i][$j][-2 * $hd_ratio],
                    $volume_points[$i + 1][$j][-2 * $hd_ratio],
                    $volume_points[$i + 1][$j + 1][-2 * $hd_ratio],
                    $volume_points[$i][$j + 1][-2 * $hd_ratio],
                ], imagecolorat($img_png, (32 * $hd_ratio - 1) - $i, 8 * $hd_ratio + $j));
                $polygons['head']['front'][] = new Polygon([
                    $volume_points[$i][$j][6 * $hd_ratio],
                    $volume_points[$i + 1][$j][6 * $hd_ratio],
                    $volume_points[$i + 1][$j + 1][6 * $hd_ratio],
                    $volume_points[$i][$j + 1][6 * $hd_ratio],
                ], imagecolorat($img_png, 8 * $hd_ratio + $i, 8 * $hd_ratio + $j));
            }
        }
        for ($j = 0; $j < 8 * $hd_ratio; $j++)
        {
            for ($faceName = -2 * $hd_ratio; $faceName < 6 * $hd_ratio; $faceName++)
            {
                $polygons['head']['right'][] = new Polygon([
                    $volume_points[0][$j][$faceName],
                    $volume_points[0][$j][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName],
                ], imagecolorat($img_png, $faceName + 2 * $hd_ratio, 8 * $hd_ratio + $j));
                $polygons['head']['left'][] = new Polygon([
                    $volume_points[8 * $hd_ratio][$j][$faceName],
                    $volume_points[8 * $hd_ratio][$j][$faceName + 1],
                    $volume_points[8 * $hd_ratio][$j + 1][$faceName + 1],
                    $volume_points[8 * $hd_ratio][$j + 1][$faceName],
                ], imagecolorat($img_png, (24 * $hd_ratio - 1) - $faceName - 2 * $hd_ratio, 8 * $hd_ratio + $j));
            }
        }
        for ($i = 0; $i < 8 * $hd_ratio; $i++)
        {
            for ($faceName = -2 * $hd_ratio; $faceName < 6 * $hd_ratio; $faceName++)
            {
                $polygons['head']['top'][] = new Polygon([
                    $volume_points[$i][0][$faceName],
                    $volume_points[$i + 1][0][$faceName],
                    $volume_points[$i + 1][0][$faceName + 1],
                    $volume_points[$i][0][$faceName + 1],
                ], imagecolorat($img_png, 8 * $hd_ratio + $i, $faceName + 2 * $hd_ratio));
                $polygons['head']['bottom'][] = new Polygon([
                    $volume_points[$i][8 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][8 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][8 * $hd_ratio][$faceName + 1],
                    $volume_points[$i][8 * $hd_ratio][$faceName + 1],
                ], imagecolorat($img_png, 16 * $hd_ratio + $i, (8 * $hd_ratio - 1) - ($faceName + 2 * $hd_ratio)));
            }
        }
        if ($display_hair)
        {
            // HELMET/HAIR
            $volume_points = [];
            for ($i = 0; $i < 9 * $hd_ratio; $i++)
            {
                for ($j = 0; $j < 9 * $hd_ratio; $j++)
                {
                    if (!isset($volume_points[$i][$j][-2 * $hd_ratio]))
                    {
                        $volume_points[$i][$j][-2 * $hd_ratio] = new Point(['x' => $i * 9 / 8 - 0.5 * $hd_ratio, 'y' => $j * 9 / 8 - 0.5 * $hd_ratio, 'z' => -2.5 * $hd_ratio]);
                    }
                    if (!isset($volume_points[$i][$j][6 * $hd_ratio]))
                    {
                        $volume_points[$i][$j][6 * $hd_ratio] = new Point(['x' => $i * 9 / 8 - 0.5 * $hd_ratio, 'y' => $j * 9 / 8 - 0.5 * $hd_ratio, 'z' => 6.5 * $hd_ratio]);
                    }
                }
            }
            for ($j = 0; $j < 9 * $hd_ratio; $j++)
            {
                for ($faceName = -2 * $hd_ratio; $faceName < 7 * $hd_ratio; $faceName++)
                {
                    if (!isset($volume_points[0][$j][$faceName]))
                    {
                        $volume_points[0][$j][$faceName] = new Point(['x' => -0.5 * $hd_ratio, 'y' => $j * 9 / 8 - 0.5 * $hd_ratio, 'z' => $faceName * 9 / 8 - 0.5 * $hd_ratio]);
                    }
                    if (!isset($volume_points[8 * $hd_ratio][$j][$faceName]))
                    {
                        $volume_points[8 * $hd_ratio][$j][$faceName] = new Point(['x' => 8.5 * $hd_ratio, 'y' => $j * 9 / 8 - 0.5 * $hd_ratio, 'z' => $faceName * 9 / 8 - 0.5 * $hd_ratio]);
                    }
                }
            }
            for ($i = 0; $i < 9 * $hd_ratio; $i++)
            {
                for ($faceName = -2 * $hd_ratio; $faceName < 7 * $hd_ratio; $faceName++)
                {
                    if (!isset($volume_points[$i][0][$faceName]))
                    {
                        $volume_points[$i][0][$faceName] = new Point(['x' => $i * 9 / 8 - 0.5 * $hd_ratio, 'y' => -0.5 * $hd_ratio, 'z' => $faceName * 9 / 8 - 0.5 * $hd_ratio]);
                    }
                    if (!isset($volume_points[$i][8 * $hd_ratio][$faceName]))
                    {
                        $volume_points[$i][8 * $hd_ratio][$faceName] = new Point(['x' => $i * 9 / 8 - 0.5 * $hd_ratio, 'y' => 8.5 * $hd_ratio, 'z' => $faceName * 9 / 8 - 0.5 * $hd_ratio]);
                    }
                }
            }
            for ($i = 0; $i < 8 * $hd_ratio; $i++)
            {
                for ($j = 0; $j < 8 * $hd_ratio; $j++)
                {
                    $polygons['helmet']['back'][] = new Polygon([
                        $volume_points[$i][$j][-2 * $hd_ratio],
                        $volume_points[$i + 1][$j][-2 * $hd_ratio],
                        $volume_points[$i + 1][$j + 1][-2 * $hd_ratio],
                        $volume_points[$i][$j + 1][-2 * $hd_ratio],
                    ], imagecolorat($img_png, 32 * $hd_ratio + (32 * $hd_ratio - 1) - $i, 8 * $hd_ratio + $j));
                    $polygons['helmet']['front'][] = new Polygon([
                        $volume_points[$i][$j][6 * $hd_ratio],
                        $volume_points[$i + 1][$j][6 * $hd_ratio],
                        $volume_points[$i + 1][$j + 1][6 * $hd_ratio],
                        $volume_points[$i][$j + 1][6 * $hd_ratio],
                    ], imagecolorat($img_png, 32 * $hd_ratio + 8 * $hd_ratio + $i, 8 * $hd_ratio + $j));
                }
            }
            for ($j = 0; $j < 8 * $hd_ratio; $j++)
            {
                for ($faceName = -2 * $hd_ratio; $faceName < 6 * $hd_ratio; $faceName++)
                {
                    $polygons['helmet']['right'][] = new Polygon([
                        $volume_points[0][$j][$faceName],
                        $volume_points[0][$j][$faceName + 1],
                        $volume_points[0][$j + 1][$faceName + 1],
                        $volume_points[0][$j + 1][$faceName],
                    ], imagecolorat($img_png, 32 * $hd_ratio + $faceName + 2 * $hd_ratio, 8 * $hd_ratio + $j));
                    $polygons['helmet']['left'][] = new Polygon([
                        $volume_points[8 * $hd_ratio][$j][$faceName],
                        $volume_points[8 * $hd_ratio][$j][$faceName + 1],
                        $volume_points[8 * $hd_ratio][$j + 1][$faceName + 1],
                        $volume_points[8 * $hd_ratio][$j + 1][$faceName],
                    ], imagecolorat($img_png, 32 * $hd_ratio + (24 * $hd_ratio - 1) - $faceName - 2 * $hd_ratio, 8 * $hd_ratio + $j));
                }
            }
            for ($i = 0; $i < 8 * $hd_ratio; $i++)
            {
                for ($faceName = -2 * $hd_ratio; $faceName < 6 * $hd_ratio; $faceName++)
                {
                    $polygons['helmet']['top'][] = new Polygon([
                        $volume_points[$i][0][$faceName],
                        $volume_points[$i + 1][0][$faceName],
                        $volume_points[$i + 1][0][$faceName + 1],
                        $volume_points[$i][0][$faceName + 1],
                    ], imagecolorat($img_png, 32 * $hd_ratio + 8 * $hd_ratio + $i, $faceName + 2 * $hd_ratio));
                    $polygons['helmet']['bottom'][] = new Polygon([
                        $volume_points[$i][8 * $hd_ratio][$faceName],
                        $volume_points[$i + 1][8 * $hd_ratio][$faceName],
                        $volume_points[$i + 1][8 * $hd_ratio][$faceName + 1],
                        $volume_points[$i][8 * $hd_ratio][$faceName + 1],
                    ], imagecolorat($img_png, 32 * $hd_ratio + 16 * $hd_ratio + $i, (8 * $hd_ratio - 1) - ($faceName + 2 * $hd_ratio)));
                }
            }
        }

        // TORSO
        $volume_points = [];
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 13 * $hd_ratio; $j++)
            {
                if (!isset($volume_points[$i][$j][0]))
                {
                    $volume_points[$i][$j][0] = new Point(['x' => $i, 'y' => $j + 8 * $hd_ratio, 'z' => 0]);
                }
                if (!isset($volume_points[$i][$j][4 * $hd_ratio]))
                {
                    $volume_points[$i][$j][4 * $hd_ratio] = new Point(['x' => $i, 'y' => $j + 8 * $hd_ratio, 'z' => 4 * $hd_ratio]);
                }
            }
        }
        for ($j = 0; $j < 13 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[0][$j][$faceName]))
                {
                    $volume_points[0][$j][$faceName] = new Point(['x' => 0, 'y' => $j + 8 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[8 * $hd_ratio][$j][$faceName]))
                {
                    $volume_points[8 * $hd_ratio][$j][$faceName] = new Point(['x' => 8 * $hd_ratio, 'y' => $j + 8 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[$i][0][$faceName]))
                {
                    $volume_points[$i][0][$faceName] = new Point(['x' => $i, 'y' => 0 + 8 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[$i][12 * $hd_ratio][$faceName]))
                {
                    $volume_points[$i][12 * $hd_ratio][$faceName] = new Point(['x' => $i, 'y' => 12 * $hd_ratio + 8 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 8 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 12 * $hd_ratio; $j++)
            {
                $polygons['torso']['back'][] = new Polygon([
                    $volume_points[$i][$j][0],
                    $volume_points[$i + 1][$j][0],
                    $volume_points[$i + 1][$j + 1][0],
                    $volume_points[$i][$j + 1][0],
                ], imagecolorat($img_png, (40 * $hd_ratio - 1) - $i, 20 * $hd_ratio + $j));
                $polygons['torso']['front'][] = new Polygon([
                    $volume_points[$i][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j + 1][4 * $hd_ratio],
                    $volume_points[$i][$j + 1][4 * $hd_ratio],
                ], imagecolorat($img_png, 20 * $hd_ratio + $i, 20 * $hd_ratio + $j));
            }
        }
        for ($j = 0; $j < 12 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['torso']['right'][] = new Polygon([
                    $volume_points[0][$j][$faceName],
                    $volume_points[0][$j][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName],
                ], imagecolorat($img_png, 16 * $hd_ratio + $faceName, 20 * $hd_ratio + $j));
                $polygons['torso']['left'][] = new Polygon([
                    $volume_points[8 * $hd_ratio][$j][$faceName],
                    $volume_points[8 * $hd_ratio][$j][$faceName + 1],
                    $volume_points[8 * $hd_ratio][$j + 1][$faceName + 1],
                    $volume_points[8 * $hd_ratio][$j + 1][$faceName],
                ], imagecolorat($img_png, (32 * $hd_ratio - 1) - $faceName, 20 * $hd_ratio + $j));
            }
        }
        for ($i = 0; $i < 8 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['torso']['top'][] = new Polygon([
                    $volume_points[$i][0][$faceName],
                    $volume_points[$i + 1][0][$faceName],
                    $volume_points[$i + 1][0][$faceName + 1],
                    $volume_points[$i][0][$faceName + 1],
                ], imagecolorat($img_png, 20 * $hd_ratio + $i, 16 * $hd_ratio + $faceName));
                $polygons['torso']['bottom'][] = new Polygon([
                    $volume_points[$i][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName + 1],
                    $volume_points[$i][12 * $hd_ratio][$faceName + 1],
                ], imagecolorat($img_png, 28 * $hd_ratio + $i, (20 * $hd_ratio - 1) - $faceName));
            }
        }
        // RIGHT ARM
        $volume_points = [];
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 13 * $hd_ratio; $j++)
            {
                if (!isset($volume_points[$i][$j][0]))
                {
                    $volume_points[$i][$j][0] = new Point(['x' => $i - 4 * $hd_ratio, 'y' => $j + 8 * $hd_ratio, 'z' => 0]);
                }
                if (!isset($volume_points[$i][$j][4 * $hd_ratio]))
                {
                    $volume_points[$i][$j][4 * $hd_ratio] = new Point(['x' => $i - 4 * $hd_ratio, 'y' => $j + 8 * $hd_ratio, 'z' => 4 * $hd_ratio]);
                }
            }
        }
        for ($j = 0; $j < 13 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[0][$j][$faceName]))
                {
                    $volume_points[0][$j][$faceName] = new Point(['x' => 0 - 4 * $hd_ratio, 'y' => $j + 8 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[8 * $hd_ratio][$j][$faceName]))
                {
                    $volume_points[4 * $hd_ratio][$j][$faceName] = new Point(['x' => 4 * $hd_ratio - 4 * $hd_ratio, 'y' => $j + 8 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[$i][0][$faceName]))
                {
                    $volume_points[$i][0][$faceName] = new Point(['x' => $i - 4 * $hd_ratio, 'y' => 0 + 8 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[$i][12 * $hd_ratio][$faceName]))
                {
                    $volume_points[$i][12 * $hd_ratio][$faceName] = new Point(['x' => $i - 4 * $hd_ratio, 'y' => 12 * $hd_ratio + 8 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 4 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 12 * $hd_ratio; $j++)
            {
                $polygons['rightArm']['back'][] = new Polygon([
                    $volume_points[$i][$j][0],
                    $volume_points[$i + 1][$j][0],
                    $volume_points[$i + 1][$j + 1][0],
                    $volume_points[$i][$j + 1][0],
                ], imagecolorat($img_png, (56 * $hd_ratio - 1) - $i, 20 * $hd_ratio + $j));
                $polygons['rightArm']['front'][] = new Polygon([
                    $volume_points[$i][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j + 1][4 * $hd_ratio],
                    $volume_points[$i][$j + 1][4 * $hd_ratio],
                ], imagecolorat($img_png, 44 * $hd_ratio + $i, 20 * $hd_ratio + $j));
            }
        }
        for ($j = 0; $j < 12 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['rightArm']['right'][] = new Polygon([
                    $volume_points[0][$j][$faceName],
                    $volume_points[0][$j][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName],
                ], imagecolorat($img_png, 40 * $hd_ratio + $faceName, 20 * $hd_ratio + $j));
                $polygons['rightArm']['left'][] = new Polygon([
                    $volume_points[4 * $hd_ratio][$j][$faceName],
                    $volume_points[4 * $hd_ratio][$j][$faceName + 1],
                    $volume_points[4 * $hd_ratio][$j + 1][$faceName + 1],
                    $volume_points[4 * $hd_ratio][$j + 1][$faceName],
                ], imagecolorat($img_png, (52 * $hd_ratio - 1) - $faceName, 20 * $hd_ratio + $j));
            }
        }
        for ($i = 0; $i < 4 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['rightArm']['top'][] = new Polygon([
                    $volume_points[$i][0][$faceName],
                    $volume_points[$i + 1][0][$faceName],
                    $volume_points[$i + 1][0][$faceName + 1],
                    $volume_points[$i][0][$faceName + 1],
                ], imagecolorat($img_png, 44 * $hd_ratio + $i, 16 * $hd_ratio + $faceName));
                $polygons['rightArm']['bottom'][] = new Polygon([
                    $volume_points[$i][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName + 1],
                    $volume_points[$i][12 * $hd_ratio][$faceName + 1],
                ], imagecolorat($img_png, 48 * $hd_ratio + $i, (20 * $hd_ratio - 1) - $faceName));
            }
        }
        // LEFT ARM
        $volume_points = [];
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 13 * $hd_ratio; $j++)
            {
                if (!isset($volume_points[$i][$j][0]))
                {
                    $volume_points[$i][$j][0] = new Point(['x' => $i + 8 * $hd_ratio, 'y' => $j + 8 * $hd_ratio, 'z' => 0]);
                }
                if (!isset($volume_points[$i][$j][4 * $hd_ratio]))
                {
                    $volume_points[$i][$j][4 * $hd_ratio] = new Point(['x' => $i + 8 * $hd_ratio, 'y' => $j + 8 * $hd_ratio, 'z' => 4 * $hd_ratio]);
                }
            }
        }
        for ($j = 0; $j < 13 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[0][$j][$faceName]))
                {
                    $volume_points[0][$j][$faceName] = new Point(['x' => 0 + 8 * $hd_ratio, 'y' => $j + 8 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[8 * $hd_ratio][$j][$faceName]))
                {
                    $volume_points[4 * $hd_ratio][$j][$faceName] = new Point(['x' => 4 * $hd_ratio + 8 * $hd_ratio, 'y' => $j + 8 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[$i][0][$faceName]))
                {
                    $volume_points[$i][0][$faceName] = new Point(['x' => $i + 8 * $hd_ratio, 'y' => 0 + 8 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[$i][12 * $hd_ratio][$faceName]))
                {
                    $volume_points[$i][12 * $hd_ratio][$faceName] = new Point(['x' => $i + 8 * $hd_ratio, 'y' => 12 * $hd_ratio + 8 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 4 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 12 * $hd_ratio; $j++)
            {
                $polygons['leftArm']['back'][] = new Polygon([
                    $volume_points[$i][$j][0],
                    $volume_points[$i + 1][$j][0],
                    $volume_points[$i + 1][$j + 1][0],
                    $volume_points[$i][$j + 1][0],
                ], imagecolorat($img_png, (56 * $hd_ratio - 1) - ((4 * $hd_ratio - 1) - $i), 20 * $hd_ratio + $j));
                $polygons['leftArm']['front'][] = new Polygon([
                    $volume_points[$i][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j + 1][4 * $hd_ratio],
                    $volume_points[$i][$j + 1][4 * $hd_ratio],
                ], imagecolorat($img_png, 44 * $hd_ratio + ((4 * $hd_ratio - 1) - $i), 20 * $hd_ratio + $j));
            }
        }
        for ($j = 0; $j < 12 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['leftArm']['right'][] = new Polygon([
                    $volume_points[0][$j][$faceName],
                    $volume_points[0][$j][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName],
                ], imagecolorat($img_png, 40 * $hd_ratio + ((4 * $hd_ratio - 1) - $faceName), 20 * $hd_ratio + $j));
                $polygons['leftArm']['left'][] = new Polygon([
                    $volume_points[4 * $hd_ratio][$j][$faceName],
                    $volume_points[4 * $hd_ratio][$j][$faceName + 1],
                    $volume_points[4 * $hd_ratio][$j + 1][$faceName + 1],
                    $volume_points[4 * $hd_ratio][$j + 1][$faceName],
                ], imagecolorat($img_png, (52 * $hd_ratio - 1) - ((4 * $hd_ratio - 1) - $faceName), 20 * $hd_ratio + $j));
            }
        }
        for ($i = 0; $i < 4 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['leftArm']['top'][] = new Polygon([
                    $volume_points[$i][0][$faceName],
                    $volume_points[$i + 1][0][$faceName],
                    $volume_points[$i + 1][0][$faceName + 1],
                    $volume_points[$i][0][$faceName + 1],
                ], imagecolorat($img_png, 44 * $hd_ratio + ((4 * $hd_ratio - 1) - $i), 16 * $hd_ratio + $faceName));
                $polygons['leftArm']['bottom'][] = new Polygon([
                    $volume_points[$i][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName + 1],
                    $volume_points[$i][12 * $hd_ratio][$faceName + 1],
                ], imagecolorat($img_png, 48 * $hd_ratio + ((4 * $hd_ratio - 1) - $i), (20 * $hd_ratio - 1) - $faceName));
            }
        }
        // RIGHT LEG
        $volume_points = [];
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 13 * $hd_ratio; $j++)
            {
                if (!isset($volume_points[$i][$j][0]))
                {
                    $volume_points[$i][$j][0] = new Point(['x' => $i, 'y' => $j + 20 * $hd_ratio, 'z' => 0]);
                }
                if (!isset($volume_points[$i][$j][4 * $hd_ratio]))
                {
                    $volume_points[$i][$j][4 * $hd_ratio] = new Point(['x' => $i, 'y' => $j + 20 * $hd_ratio, 'z' => 4 * $hd_ratio]);
                }
            }
        }
        for ($j = 0; $j < 13 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[0][$j][$faceName]))
                {
                    $volume_points[0][$j][$faceName] = new Point(['x' => 0, 'y' => $j + 20 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[8 * $hd_ratio][$j][$faceName]))
                {
                    $volume_points[4 * $hd_ratio][$j][$faceName] = new Point(['x' => 4 * $hd_ratio, 'y' => $j + 20 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[$i][0][$faceName]))
                {
                    $volume_points[$i][0][$faceName] = new Point(['x' => $i, 'y' => 0 + 20 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[$i][12 * $hd_ratio][$faceName]))
                {
                    $volume_points[$i][12 * $hd_ratio][$faceName] = new Point(['x' => $i, 'y' => 12 * $hd_ratio + 20 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 4 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 12 * $hd_ratio; $j++)
            {
                $polygons['rightLeg']['back'][] = new Polygon([
                    $volume_points[$i][$j][0],
                    $volume_points[$i + 1][$j][0],
                    $volume_points[$i + 1][$j + 1][0],
                    $volume_points[$i][$j + 1][0],
                ], imagecolorat($img_png, (16 * $hd_ratio - 1) - $i, 20 * $hd_ratio + $j));
                $polygons['rightLeg']['front'][] = new Polygon([
                    $volume_points[$i][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j + 1][4 * $hd_ratio],
                    $volume_points[$i][$j + 1][4 * $hd_ratio],
                ], imagecolorat($img_png, 4 * $hd_ratio + $i, 20 * $hd_ratio + $j));
            }
        }
        for ($j = 0; $j < 12 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['rightLeg']['right'][] = new Polygon([
                    $volume_points[0][$j][$faceName],
                    $volume_points[0][$j][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName],
                ], imagecolorat($img_png, 0 + $faceName, 20 * $hd_ratio + $j));
                $polygons['rightLeg']['left'][] = new Polygon([
                    $volume_points[4 * $hd_ratio][$j][$faceName],
                    $volume_points[4 * $hd_ratio][$j][$faceName + 1],
                    $volume_points[4 * $hd_ratio][$j + 1][$faceName + 1],
                    $volume_points[4 * $hd_ratio][$j + 1][$faceName],
                ], imagecolorat($img_png, (12 * $hd_ratio - 1) - $faceName, 20 * $hd_ratio + $j));
            }
        }
        for ($i = 0; $i < 4 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['rightLeg']['top'][] = new Polygon([
                    $volume_points[$i][0][$faceName],
                    $volume_points[$i + 1][0][$faceName],
                    $volume_points[$i + 1][0][$faceName + 1],
                    $volume_points[$i][0][$faceName + 1],
                ], imagecolorat($img_png, 4 * $hd_ratio + $i, 16 * $hd_ratio + $faceName));
                $polygons['rightLeg']['bottom'][] = new Polygon([
                    $volume_points[$i][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName + 1],
                    $volume_points[$i][12 * $hd_ratio][$faceName + 1],
                ], imagecolorat($img_png, 8 * $hd_ratio + $i, (20 * $hd_ratio - 1) - $faceName));
            }
        }
        // LEFT LEG
        $volume_points = [];
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 13 * $hd_ratio; $j++)
            {
                if (!isset($volume_points[$i][$j][0]))
                {
                    $volume_points[$i][$j][0] = new Point(['x' => $i + 4 * $hd_ratio, 'y' => $j + 20 * $hd_ratio, 'z' => 0]);
                }
                if (!isset($volume_points[$i][$j][4 * $hd_ratio]))
                {
                    $volume_points[$i][$j][4 * $hd_ratio] = new Point(['x' => $i + 4 * $hd_ratio, 'y' => $j + 20 * $hd_ratio, 'z' => 4 * $hd_ratio]);
                }
            }
        }
        for ($j = 0; $j < 13 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[0][$j][$faceName]))
                {
                    $volume_points[0][$j][$faceName] = new Point(['x' => 0 + 4 * $hd_ratio, 'y' => $j + 20 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[8 * $hd_ratio][$j][$faceName]))
                {
                    $volume_points[4 * $hd_ratio][$j][$faceName] = new Point(['x' => 4 * $hd_ratio + 4 * $hd_ratio, 'y' => $j + 20 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 9 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 5 * $hd_ratio; $faceName++)
            {
                if (!isset($volume_points[$i][0][$faceName]))
                {
                    $volume_points[$i][0][$faceName] = new Point(['x' => $i + 4 * $hd_ratio, 'y' => 0 + 20 * $hd_ratio, 'z' => $faceName]);
                }
                if (!isset($volume_points[$i][12 * $hd_ratio][$faceName]))
                {
                    $volume_points[$i][12 * $hd_ratio][$faceName] = new Point(['x' => $i + 4 * $hd_ratio, 'y' => 12 * $hd_ratio + 20 * $hd_ratio, 'z' => $faceName]);
                }
            }
        }
        for ($i = 0; $i < 4 * $hd_ratio; $i++)
        {
            for ($j = 0; $j < 12 * $hd_ratio; $j++)
            {
                $polygons['leftLeg']['back'][] = new Polygon([
                    $volume_points[$i][$j][0],
                    $volume_points[$i + 1][$j][0],
                    $volume_points[$i + 1][$j + 1][0],
                    $volume_points[$i][$j + 1][0],
                ], imagecolorat($img_png, (16 * $hd_ratio - 1) - ((4 * $hd_ratio - 1) - $i), 20 * $hd_ratio + $j));
                $polygons['leftLeg']['front'][] = new Polygon([
                    $volume_points[$i][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j][4 * $hd_ratio],
                    $volume_points[$i + 1][$j + 1][4 * $hd_ratio],
                    $volume_points[$i][$j + 1][4 * $hd_ratio],
                ], imagecolorat($img_png, 4 * $hd_ratio + ((4 * $hd_ratio - 1) - $i), 20 * $hd_ratio + $j));
            }
        }
        for ($j = 0; $j < 12 * $hd_ratio; $j++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['leftLeg']['right'][] = new Polygon([
                    $volume_points[0][$j][$faceName],
                    $volume_points[0][$j][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName + 1],
                    $volume_points[0][$j + 1][$faceName],
                ], imagecolorat($img_png, 0 + ((4 * $hd_ratio - 1) - $faceName), 20 * $hd_ratio + $j));
                $polygons['leftLeg']['left'][] = new Polygon([
                    $volume_points[4 * $hd_ratio][$j][$faceName],
                    $volume_points[4 * $hd_ratio][$j][$faceName + 1],
                    $volume_points[4 * $hd_ratio][$j + 1][$faceName + 1],
                    $volume_points[4 * $hd_ratio][$j + 1][$faceName],
                ], imagecolorat($img_png, (12 * $hd_ratio - 1) - ((4 * $hd_ratio - 1) - $faceName), 20 * $hd_ratio + $j));
            }
        }
        for ($i = 0; $i < 4 * $hd_ratio; $i++)
        {
            for ($faceName = 0; $faceName < 4 * $hd_ratio; $faceName++)
            {
                $polygons['leftLeg']['top'][] = new Polygon([
                    $volume_points[$i][0][$faceName],
                    $volume_points[$i + 1][0][$faceName],
                    $volume_points[$i + 1][0][$faceName + 1],
                    $volume_points[$i][0][$faceName + 1],
                ], imagecolorat($img_png, 4 * $hd_ratio + ((4 * $hd_ratio - 1) - $i), 16 * $hd_ratio + $faceName));
                $polygons['leftLeg']['bottom'][] = new Polygon([
                    $volume_points[$i][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName],
                    $volume_points[$i + 1][12 * $hd_ratio][$faceName + 1],
                    $volume_points[$i][12 * $hd_ratio][$faceName + 1],
                ], imagecolorat($img_png, 8 * $hd_ratio + ((4 * $hd_ratio - 1) - $i), (20 * $hd_ratio - 1) - $faceName));
            }
        }

        $times[] = ['Polygon-generation', $this->microtime_float()];

        foreach ($polygons['head'] as $face)
        {
            foreach ($face as $poly)
            {
                /** @var Polygon $poly */
                $poly->preProject(4, 8, 2, $parts_angles['head']['cos_alpha'], $parts_angles['head']['sin_alpha'], $parts_angles['head']['cos_omega'], $parts_angles['head']['sin_omega']);
            }
        }
        if ($display_hair)
        {
            foreach ($polygons['helmet'] as $face)
            {
                foreach ($face as $poly)
                {
                    /** @var Polygon $poly */
                    $poly->preProject(4, 8, 2, $parts_angles['head']['cos_alpha'], $parts_angles['head']['sin_alpha'], $parts_angles['head']['cos_omega'], $parts_angles['head']['sin_omega']);
                }
            }
        }

        foreach ($polygons['rightArm'] as $face)
        {
            foreach ($face as $poly)
            {
                /** @var Polygon $poly */
                $poly->preProject(-2, 8, 2, $parts_angles['rightArm']['cos_alpha'], $parts_angles['rightArm']['sin_alpha'], $parts_angles['rightArm']['cos_omega'], $parts_angles['rightArm']['sin_omega']);
            }
        }
        foreach ($polygons['leftArm'] as $face)
        {
            /** @var Polygon $poly */
            foreach ($face as $poly)
            {
                $poly->preProject(10, 8, 2, $parts_angles['leftArm']['cos_alpha'], $parts_angles['leftArm']['sin_alpha'], $parts_angles['leftArm']['cos_omega'], $parts_angles['leftArm']['sin_omega']);
            }
        }
        foreach ($polygons['rightLeg'] as $face)
        {
            /** @var Polygon $poly */
            foreach ($face as $poly)
            {
                $poly->preProject(2, 20, ($parts_angles['rightLeg']['sin_alpha'] < 0 ? 0 : 4), $parts_angles['rightLeg']['cos_alpha'], $parts_angles['rightLeg']['sin_alpha'], $parts_angles['rightLeg']['cos_omega'], $parts_angles['rightLeg']['sin_omega']);
            }
        }
        foreach ($polygons['leftLeg'] as $face)
        {
            /** @var Polygon $poly */
            foreach ($face as $poly)
            {
                $poly->preProject(6, 20, ($parts_angles['leftLeg']['sin_alpha'] < 0 ? 0 : 4), $parts_angles['leftLeg']['cos_alpha'], $parts_angles['leftLeg']['sin_alpha'], $parts_angles['leftLeg']['cos_omega'], $parts_angles['leftLeg']['sin_omega']);
            }
        }

        $times[] = ['Members-rotation', $this->microtime_float()];

        foreach ($polygons as $piece)
        {
            foreach ($piece as $face)
            {
                foreach ($face as $poly)
                {
                    if (!$poly->isProjected())
                    {
                        $poly->project();
                    }
                }
            }
        }

        $times[] = ['Projection-plan', $this->microtime_float()];

        $width = static::$maxX - static::$minX;
        $height = static::$maxY - static::$minY;

        // Handle the ratio
        $min_ratio = 2;
        $ratio = intval(Request::get('ratio'));
        $ratio = ($ratio < $min_ratio) ? $min_ratio : $ratio;

        if (SkinRenderer::SECONDS_TO_CACHE > 0)
        {
            $ts = gmdate("D, d M Y H:i:s", time() + SkinRenderer::SECONDS_TO_CACHE) . ' GMT';
            header('Expires: ' . $ts);
            header('Pragma: cache');
            header('Cache-Control: max-age=' . SkinRenderer::SECONDS_TO_CACHE);
        }

        $image = null;
        if (Request::get('format') == 'svg')
        {
            header('Content-Type: image/svg+xml');
            echo '<?xml version="1.0" standalone="no"?>
        <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"
        "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">

        <svg width="100%" height="100%" version="1.1"
        xmlns="http://www.w3.org/2000/svg" viewBox="' . static::$minX . ' ' . static::$minY . ' ' . $width . ' ' . $height . '">';
        }
        else
        {
            //header('Content-type: image/png');
            $image = imagecreatetruecolor($ratio * $width + 1, $ratio * $height + 1);
            imagesavealpha($image, true);
            $trans_colour = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $trans_colour);
        }

        $display_order = [];
        if (in_array('top', $front_faces))
        {
            if (in_array('right', $front_faces))
            {
                $display_order[] = ['leftLeg' => $back_faces];
                $display_order[] = ['leftLeg' => $visible_faces['leftLeg']['front']];
                $display_order[] = ['rightLeg' => $back_faces];
                $display_order[] = ['rightLeg' => $visible_faces['rightLeg']['front']];
                $display_order[] = ['leftArm' => $back_faces];
                $display_order[] = ['leftArm' => $visible_faces['leftArm']['front']];
                $display_order[] = ['torso' => $back_faces];
                $display_order[] = ['torso' => $visible_faces['torso']['front']];
                $display_order[] = ['rightArm' => $back_faces];
                $display_order[] = ['rightArm' => $visible_faces['rightArm']['front']];
            }
            else
            {
                $display_order[] = ['rightLeg' => $back_faces];
                $display_order[] = ['rightLeg' => $visible_faces['rightLeg']['front']];
                $display_order[] = ['leftLeg' => $back_faces];
                $display_order[] = ['leftLeg' => $visible_faces['leftLeg']['front']];
                $display_order[] = ['rightArm' => $back_faces];
                $display_order[] = ['rightArm' => $visible_faces['rightArm']['front']];
                $display_order[] = ['torso' => $back_faces];
                $display_order[] = ['torso' => $visible_faces['torso']['front']];
                $display_order[] = ['leftArm' => $back_faces];
                $display_order[] = ['leftArm' => $visible_faces['leftArm']['front']];
            }
            $display_order[] = ['helmet' => $back_faces];
            $display_order[] = ['head' => $back_faces];
            $display_order[] = ['head' => $visible_faces['head']['front']];
            $display_order[] = ['helmet' => $visible_faces['head']['front']];
        }
        else
        {
            $display_order[] = ['helmet' => $back_faces];
            $display_order[] = ['head' => $back_faces];
            $display_order[] = ['head' => $visible_faces['head']['front']];
            $display_order[] = ['helmet' => $visible_faces['head']['front']];
            if (in_array('right', $front_faces))
            {
                $display_order[] = ['leftArm' => $back_faces];
                $display_order[] = ['leftArm' => $visible_faces['leftArm']['front']];
                $display_order[] = ['torso' => $back_faces];
                $display_order[] = ['torso' => $visible_faces['torso']['front']];
                $display_order[] = ['rightArm' => $back_faces];
                $display_order[] = ['rightArm' => $visible_faces['rightArm']['front']];
                $display_order[] = ['leftLeg' => $back_faces];
                $display_order[] = ['leftLeg' => $visible_faces['leftLeg']['front']];
                $display_order[] = ['rightLeg' => $back_faces];
                $display_order[] = ['rightLeg' => $visible_faces['rightLeg']['front']];
            }
            else
            {
                $display_order[] = ['rightArm' => $back_faces];
                $display_order[] = ['rightArm' => $visible_faces['rightArm']['front']];
                $display_order[] = ['torso' => $back_faces];
                $display_order[] = ['torso' => $visible_faces['torso']['front']];
                $display_order[] = ['leftArm' => $back_faces];
                $display_order[] = ['leftArm' => $visible_faces['leftArm']['front']];
                $display_order[] = ['rightLeg' => $back_faces];
                $display_order[] = ['rightLeg' => $visible_faces['rightLeg']['front']];
                $display_order[] = ['leftLeg' => $back_faces];
                $display_order[] = ['leftLeg' => $visible_faces['leftLeg']['front']];
            }
        }

        $times[] = ['Calculated-display-faces', $this->microtime_float()];

        foreach ($display_order as $pieces)
        {
            foreach ($pieces as $piece => $faces)
            {
                foreach ($faces as $face)
                {
                    foreach ($polygons[$piece][$face] as $poly)
                    {
                        if (Request::get('format') == 'svg')
                        {
                            echo $poly->getSvgPolygon(1);
                        }
                        else
                        {
                            $poly->addPngPolygon($image, static::$minX, static::$minY, $ratio);
                        }
                    }
                }
            }
        }

        $times[] = ['Display-image', $this->microtime_float()];

        if (Request::get('format') == 'svg')
        {
            echo '</svg>' . "\n";
            for ($i = 1; $i < count($times); $i++)
            {
                echo '<!-- ' . ($times[$i][1] - $times[$i - 1][1]) * 1000 . 'ms : ' . $times[$i][0] . ' -->' . "\n";
            }
            echo '<!-- TOTAL : ' . ($times[count($times) - 1][1] - $times[0][1]) * 1000 . 'ms -->' . "\n";
        }
        else
        {
            imagepng($image);
            imagedestroy($image);
            for ($i = 1; $i < count($times); $i++)
            {
                header('generation-time-' . $i . '-' . $times[$i][0] . ': ' . ($times[$i][1] - $times[$i - 1][1]) * 1000 . 'ms');
            }
            header('generation-time-' . count($times) . '-TOTAL: ' . ($times[count($times) - 1][1] - $times[0][1]) * 1000 . 'ms');
        }
    }

    /**
     * Returns timing in microseconds - used to calculate time taken to process images
     * @return float
     */
    private function microtime_float(): float
    {
        $micro = explode(' ', microtime());
        return (float)$micro[0] + (float)$micro[1];
    }

    /**
     * @return CubePoint[];
     */
    private function generateCubePoints(): array
    {
        $cubePoints = [];
        $cubePoints[0] = new CubePoint(new Point(['x' => 0, 'y' => 0, 'z' => 0]), ['back', 'right', 'top']);
        $cubePoints[1] = new CubePoint(new Point(['x' => 0, 'y' => 0, 'z' => 1]), ['front', 'right', 'top']);
        $cubePoints[2] = new CubePoint(new Point(['x' => 0, 'y' => 1, 'z' => 0]), ['back', 'right', 'bottom']);
        $cubePoints[3] = new CubePoint(new Point(['x' => 0, 'y' => 1, 'z' => 1]), ['front', 'right', 'bottom']);
        $cubePoints[4] = new CubePoint(new Point(['x' => 1, 'y' => 0, 'z' => 0]), ['back', 'left', 'top']);
        $cubePoints[5] = new CubePoint(new Point(['x' => 1, 'y' => 0, 'z' => 1]), ['front', 'left', 'top']);
        $cubePoints[6] = new CubePoint(new Point(['x' => 1, 'y' => 1, 'z' => 0]), ['back', 'left', 'bottom']);
        $cubePoints[7] = new CubePoint(new Point(['x' => 1, 'y' => 1, 'z' => 1]), ['front', 'left', 'bottom']);
        return $cubePoints;
    }
}