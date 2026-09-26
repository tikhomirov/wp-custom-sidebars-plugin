# Custom Classic Sidebars (`wp-custom-sidebars-plugin`)

![WordPress Plugin](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP Support](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2%20%7C%208.3-777BB4.svg)
![License](https://img.shields.io/badge/License-GPLv2-green.svg)

![Custom Sidebars Interface](https://github.com/user-attachments/assets/c60088f4-2dcf-4f38-9329-440c0efaefa0)

Легкий и удобный WordPress плагин для динамического создания неограниченного количества сайдбаров (виджетов) в админ-панели и их вывода в любой точке сайта с помощью шорткода или PHP.

---

## 🚀 Возможности

- ➕ **Динамическое добавление сайдбаров:** Создавайте новые области виджетов прямо на странице **Внешний вид → Виджеты**.
- 🗑️ **AJAX-удаление:** Быстрое удаление ненужных сайдбаров без перезагрузки страницы.
- 🧩 **Шорткод `[custom_sidebars]`:** Вывод любого созданного сайдбара в записях, страницах или конструкторах.
- 💻 **PHP API:** Простой вызов в шаблонах темы.
- 📦 **Поддержка Composer (`wordpress-plugin`):** Легкая интеграция в сборки на базе Roots Bedrock или стандартного Composer.

---

## 📥 Установка

### Через Composer (рекомендуется)
```bash
composer config repositories.tikhomirov-wp-custom-sidebars-plugin git https://github.com/tikhomirov/wp-custom-sidebars-plugin.git
composer require tikhomirov/wp-custom-sidebars-plugin
```

### Вручную
1. Скачайте ZIP-архив репозитория.
2. Распакуйте в директорию `/wp-content/plugins/wp-custom-sidebars-plugin/`.
3. Активируйте плагин в админ-панели **Плагины → Установленные**.

---

## 💻 Использование

### 1. Создание сайдбара
1. Перейдите в **Внешний вид → Виджеты**.
2. Внизу блока сайдбаров введите название нового сайдбара и нажмите **Add Sidebar**.
3. Добавьте в созданный сайдбар нужные виджеты.

### 2. Вывод через Шорткод
```html
[custom_sidebars id="my-sidebar-slug"]
```

### 3. Вывод через PHP в шаблоне темы
```php
<?php
if (function_exists('dynamic_sidebar')) {
    dynamic_sidebar('my-sidebar-slug');
}
?>
```

---

## 🛠️ Требования

- **WordPress:** 5.0 или выше
- **PHP:** 7.4, 8.0, 8.1, 8.2, 8.3
