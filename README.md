project files - несколько файлов проекта. 
Проект делался не для того, чтобы его показать кому-то как пример хорошего кода, 
а по принципу "надо сделать скорее... и на тебе еще пачку других задач, это тоже надо сделать вчера", так-что не судите строго.

project_files/app/Http/ProductController.php - контроллер "товара" тут есть экшены как для "админки", так и для публичной части.
project_files/app/Models/Product.php - модель для "товара"
project_files/resources/views/admin - админские "вьюхи" для товара и некоторые зависимости
project_files/resources/views/front - "вьюхи" для публичной части для сущности товара и некоторые зависимости
project_files/resources/js/admin.js - админский нативный "js". Это исходники, в код компилируются при помощи webpack 
project_files/resources/js/index.js - публичный нативный "js". Это исходники, в код компилируются при помощи webpack


project_files/app/Models/CodeGenerator.php - "генератор кода" - приблуда, которая создает типовую модель, контроллер, роуты, вьюхи, пункт меню админки.
Дальше эту модель можно "допилить" и сделать из неё то, что хочется. Функционал для ускорения разработки, идея почерпнута из фреймворка Yii (Gii)  
project_files/app/Http/CommonController.php codeMakerRender - экшен для генератора кода
project_files/app/Http/CommonController.php codeMakerExecute - экшен для генератора кода


project_files/app/Traits/SingleImage.php -  трейт, который позволяет "прикреплять" к модели картинку (на самом деле несколько нумерованных), 
ресайзить её под любые размеры, выставлять расширения jpg, png, webp при помощи параметров вызова компонента, готовые картинки кэшируются.  
Функционал был сделан, когда я не проникся spatie/laravel-medialibrary. Впрочем, он какими-то фичами он лучше, чем spatie/laravel-medialibrary, 
можно использовать их параллельно.

tabs - javascript табы, компактные, без зависимостей




Еще одна необычная, но довольно давняя работа - сайт с трехмерными модельками на Three.js админка позволяет создать товар,
загрузить модельку и текстуры к ней, настроить освещение, позиционирование товара. Код откровенно стремный, показывать стыдно. Но работает уже несколько лет. 
https://logo.i-pac.ru/catalog/bumazhnaya-upakovka/bumazhnaya-korobka-dlya-fastfuda - пример страницы для просмотра товара (надо нажать на 3d)
https://logo.i-pac.ru/print-logo/71 - пример странички, где можно рисовать, размещать картинки и надписи на модели. Инструмент сделан на Fabric.js 
Требует обязательно ввести почту, требование владельца-заказчика сайта.