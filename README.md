meerkat-thumb
=============

Thumb module for MeerkatCMF

$model = ORM::factory('User', 1);
$thumb = Meerkat\Thumb\Thumb::factory($model->object_name(), $model->pk());
$thumb = Meerkat\Thumb\Thumb::factory('user', 1);
Debug::info($thumb->make(DOCROOT . '3.f55a01add61618f5789e5f49409b6dd0.jpg'));;
Debug::info($thumb->make('http://dfedorov.ru/wp-content/uploads/2012/10/DSC_0421-655x364.jpg'));
Debug::info($thumb->rebuild());