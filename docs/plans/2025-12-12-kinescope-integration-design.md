# Дизайн интеграции Kinescope видеоплеера

**Дата:** 2025-12-12
**Статус:** Утверждён

## Обзор

Интеграция Kinescope видеоплеера в ManySales для поддержки российского видеохостинга наравне с YouTube. Админы и продавцы смогут добавлять Kinescope видео к товарам, а клиенты - просматривать их на странице товара.

## Принятые решения

### 1. Управление видео
**Выбрано:** Только embed ссылок (Вариант А)
- Админы и продавцы загружают видео в Kinescope самостоятельно
- В ManySales вставляют только ссылку на готовое видео
- Не требуется API токены и сложная интеграция
- Проще в реализации и поддержке

### 2. Формат ввода
**Выбрано:** Любой формат (Вариант А)
- Система автоматически распознаёт и извлекает VIDEO_ID
- Поддерживаемые форматы:
  - `https://kinescope.io/embed/VIDEO_ID`
  - `https://kinescope.io/VIDEO_ID`
  - `VIDEO_ID` (только ID)
- Максимальное удобство для пользователей

### 3. Работа с YouTube
**Выбрано:** Выбор провайдера (Вариант А)
- Dropdown с выбором: YouTube / Kinescope
- Для каждого товара только один тип видео
- Поле `video_provider` хранит выбор ('youtube' или 'kinescope')
- Явный и понятный интерфейс

### 4. Отображение на странице товара
**Выбрано:** Над блоком "Детальное описание"
- Плеер размещается над описанием товара
- Используется Kinescope iframe player
- Responsive дизайн с aspect-ratio 16:9

## Архитектура решения

### База данных

Используем существующие поля в таблице `products`:
- `video_provider` VARCHAR - хранит 'youtube' или 'kinescope'
- `video_url` TEXT - хранит введённую пользователем ссылку

Миграции не требуются.

### Backend компоненты

#### KinescopeService (app/Services/KinescopeService.php)

Сервис для обработки Kinescope ссылок.

**Методы:**

1. **parseVideoUrl(string $url): ?string**
   - Извлекает VIDEO_ID из любого формата ссылки
   - Возвращает чистый VIDEO_ID или null

2. **getEmbedUrl(string $videoId): string**
   - Формирует embed URL: `https://kinescope.io/embed/{VIDEO_ID}`
   - Используется для iframe

3. **validateUrl(string $url): bool**
   - Проверяет валидность Kinescope ссылки
   - Поддерживает все три формата

**Паттерны распознавания:**
```php
// Pattern 1: https://kinescope.io/embed/VIDEO_ID
// Pattern 2: https://kinescope.io/VIDEO_ID
// Pattern 3: VIDEO_ID (UUID или другой формат ID)
```

**Обработка ошибок:**
- Невалидная ссылка → возврат null
- Логирование попыток с неверными ссылками
- Понятные сообщения для пользователя

#### Обновление ProductService

Добавить валидацию video_provider и video_url:
- Проверка соответствия провайдера и URL
- Для kinescope - вызов `KinescopeService::validateUrl()`
- Для youtube - существующая логика

### Frontend компоненты

#### Admin/Vendor формы

**Файлы для обновления:**
- `resources/views/admin-views/product/add/_product-video.blade.php`
- `resources/views/admin-views/product/update/_product-video.blade.php`
- `resources/views/vendor-views/product/add-new.blade.php`
- `resources/views/vendor-views/product/edit.blade.php`

**Изменения:**

1. **Dropdown выбора провайдера:**
```html
<select name="video_provider" id="video_provider" class="form-control">
    <option value="">{{ translate('not_selected') }}</option>
    <option value="youtube">YouTube</option>
    <option value="kinescope">Kinescope</option>
</select>
```

2. **Динамические подсказки:**
- JavaScript слушает изменение dropdown
- Меняет placeholder и tooltip в зависимости от выбора
- YouTube: "Ex: https://www.youtube.com/embed/VIDEO_ID"
- Kinescope: "Ex: https://kinescope.io/VIDEO_ID или https://kinescope.io/embed/VIDEO_ID"

3. **Валидация:**
- Client-side: проверка формата перед отправкой
- Server-side: через ProductService
- Сообщения об ошибках на русском языке

#### Страница товара (клиентская часть)

**Файлы для обновления:**
- `resources/themes/default/web-views/products/details.blade.php`
- `resources/themes/theme_aster/theme-views/product/details.blade.php`

**Логика отображения:**

```php
@if($product->video_provider === 'kinescope' && $product->video_url)
    @php
        $videoId = \App\Services\KinescopeService::parseVideoUrl($product->video_url);
    @endphp

    @if($videoId)
        <div class="product-video mb-4">
            <h5>{{ translate('product_video') }}</h5>
            <div class="ratio ratio-16x9">
                <iframe
                    src="{{ \App\Services\KinescopeService::getEmbedUrl($videoId) }}"
                    frameborder="0"
                    allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    @endif

@elseif($product->video_provider === 'youtube' && $product->video_url)
    {{-- Существующая логика YouTube --}}
@endif
```

**Размещение:**
- Над блоком "Детальное описание"
- После основной информации о товаре

**Стили:**
- Responsive с использованием Bootstrap ratio-16x9
- На мобильных устройствах - адаптивная высота
- Минимальная высота: 300px на десктопе, 200px на мобильных

## Потоки данных

### 1. Добавление товара с Kinescope видео

```
Админ/Продавец
  ↓
Выбирает "Kinescope" в dropdown
  ↓
Вставляет ссылку (любой формат)
  ↓
Client-side валидация (JavaScript)
  ↓
Отправка формы
  ↓
ProductService валидация
  ↓
KinescopeService::validateUrl()
  ↓
Сохранение в БД (video_provider='kinescope', video_url=ссылка)
```

### 2. Отображение видео клиенту

```
Клиент открывает страницу товара
  ↓
Blade проверяет video_provider
  ↓
Если 'kinescope':
  ↓
KinescopeService::parseVideoUrl() → VIDEO_ID
  ↓
KinescopeService::getEmbedUrl(VIDEO_ID) → embed URL
  ↓
Рендер iframe с embed URL
  ↓
Клиент видит плеер
```

## Обработка ошибок

### Невалидная ссылка
- **Backend:** Возврат ошибки валидации
- **Frontend:** Красное сообщение под полем
- **Текст:** "Неверный формат ссылки Kinescope. Используйте ссылку вида kinescope.io/VIDEO_ID"

### VIDEO_ID не извлекается
- **Backend:** Логирование ошибки
- **Frontend:** Плеер не показывается
- **Fallback:** Нет видео (не показываем блок)

### Kinescope недоступен
- **Frontend:** Стандартная обработка iframe (пустой блок или сообщение Kinescope)
- Клиент видит ошибку от Kinescope

## Тестирование

### Unit тесты

**KinescopeServiceTest:**
- `testParseEmbedUrl()` - проверка embed формата
- `testParsePlayUrl()` - проверка play формата
- `testParseVideoId()` - проверка только ID
- `testInvalidUrl()` - проверка невалидных ссылок
- `testGetEmbedUrl()` - проверка генерации embed URL

### Интеграционные тесты

**ProductCreationTest:**
- Создание товара с Kinescope видео
- Валидация неверного формата
- Сохранение корректных данных

### Ручное тестирование

**Admin панель:**
- [ ] Выбор Kinescope провайдера
- [ ] Вставка embed ссылки
- [ ] Вставка play ссылки
- [ ] Вставка только VIDEO_ID
- [ ] Валидация неверной ссылки
- [ ] Редактирование существующего видео
- [ ] Смена провайдера YouTube → Kinescope

**Vendor панель:**
- [ ] То же что и для Admin

**Клиентская часть:**
- [ ] Отображение плеера над описанием
- [ ] Responsive на мобильных
- [ ] Autoplay не работает (по умолчанию)
- [ ] Fullscreen работает
- [ ] Отображение в обеих темах (default, theme_aster)

## Безопасность

### XSS защита
- Все выводимые данные экранируются через Blade {{ }}
- VIDEO_ID валидируется перед использованием

### CSRF
- Используем стандартные Laravel CSRF токены в формах

### Валидация
- Client-side + Server-side валидация
- Whitelist разрешённых провайдеров

## Производительность

### Оптимизации
- Парсинг VIDEO_ID происходит при рендере (кэширование не требуется)
- Iframe загружается lazy (браузер контролирует)
- Минимальные запросы к KinescopeService

### Мониторинг
- Логирование ошибок парсинга
- Отслеживание невалидных ссылок

## Миграция существующих данных

Не требуется - новая функциональность:
- Существующие товары с YouTube остаются без изменений
- Поле `video_provider` для старых записей = NULL или 'youtube'

## План реализации

### Этап 1: Backend
1. Создать `KinescopeService`
2. Написать unit тесты
3. Обновить `ProductService` с валидацией

### Этап 2: Admin/Vendor формы
1. Обновить partial `_product-video.blade.php`
2. Добавить JavaScript для динамики
3. Обновить vendor формы
4. Тестирование форм

### Этап 3: Frontend отображение
1. Обновить `details.blade.php` (default theme)
2. Обновить `details.blade.php` (theme_aster)
3. Добавить стили если нужно
4. Тестирование отображения

### Этап 4: Тестирование и документация
1. Интеграционные тесты
2. Ручное тестирование
3. Обновить README если нужно
4. Деплой

## Альтернативы (не выбраны)

### Полная API интеграция
- Загрузка видео через API Kinescope
- Требует токены, сложнее
- Отклонено в пользу простоты

### Автоопределение провайдера
- Определение по URL без dropdown
- Менее явно для пользователя
- Отклонено в пользу явного выбора

### Видео в галерее товара
- Плеер как слайд в галерее
- Сложнее интеграция
- Отклонено: размещение над описанием проще

## Риски и митигации

### Риск: Изменение формата Kinescope ссылок
**Митигация:** Поддержка множественных паттернов, легко добавить новые

### Риск: Kinescope меняет embed API
**Митигация:** Используем стандартный iframe embed, редко меняется

### Риск: Пользователи вводят неправильные ссылки
**Митигация:** Понятные подсказки, примеры, валидация

## Документация для пользователей

### Для админов/продавцов
**Как добавить Kinescope видео:**
1. В форме товара найдите секцию "Видео товара"
2. Выберите "Kinescope" в выпадающем списке
3. Вставьте ссылку на видео из Kinescope (любой формат)
4. Сохраните товар

**Где взять ссылку:**
1. Откройте ваше видео в Kinescope
2. Скопируйте URL из адресной строки или кнопку "Поделиться"
3. Вставьте в поле видео в ManySales

## Заключение

Дизайн обеспечивает простую и надёжную интеграцию Kinescope в существующую систему видео для товаров. Минимальные изменения в коде, максимальное удобство для пользователей.

**Готово к реализации:** ✅
