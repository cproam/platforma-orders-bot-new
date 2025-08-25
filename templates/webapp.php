<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($title ?? 'Заявки') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        :root {
            --bg: var(--tg-theme-bg-color, #0f141a);
            --text: var(--tg-theme-text-color, #eaeef3);
            --hint: var(--tg-theme-hint-color, #94a3b8);
            --link: var(--tg-theme-link-color, #6ab3f3);
            --btn: var(--tg-theme-button-color, #2ea6ff);
            --btn-text: var(--tg-theme-button-text-color, #fff);
            --sec-bg: var(--tg-theme-secondary-bg-color, #151c23);
            --ok: #22c55e;
            --danger: #ef4444;
            --card: rgba(255, 255, 255, .06);
            --shadow: 0 10px 30px rgba(0, 0, 0, .25);
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            padding: 24px;
            background: radial-gradient(1200px 600px at 10% -10%, rgba(255, 255, 255, .05), transparent), var(--bg);
            color: var(--text);
            font: 16px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"
        }

        .container {
            /* max-width: 780px;
            margin: 0 auto;
            background: linear-gradient(180deg, var(--card), transparent);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 18px;
            padding: 24px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(6px) */
        }

        h1 {
            margin: 0 0 16px;
            letter-spacing: .3px
        }

        .hint {
            color: var(--hint);
            margin-top: 4px;
        }

        form {
            margin-top: 18px
        }

        .section {
            overflow: hidden;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, .06);
            background: var(--sec-bg);
            padding: 16px;
            margin-top: 12px;
            transform-origin: top center;
            transition: opacity .25s ease, transform .25s ease, padding .25s ease, margin .25s ease, border-width .25s ease
        }

        .section.hidden {
            opacity: 0;
            transform: scaleY(.98);
            height: 0;
            padding: 0;
            margin: 0;
            border-width: 0;
            pointer-events: none
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px
        }

        .row>.field {
            display: flex;
            flex-direction: column;
            gap: 6px
        }

        .field {
            margin-bottom: 12px
        }

        .field label {
            font-weight: 600;
            color: var(--hint);
            font-size: 13px
        }

        .field input,
        .field textarea,
        .field select {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, .1);
            background: var(--bg);
            color: var(--text);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            box-sizing: border-box;
        }

        .field input:focus,
        .field textarea:focus,
        .field select:focus {
            border-color: var(--link);
            box-shadow: 0 0 0 3px rgba(106, 179, 243, .15)
        }

        .field textarea {
            min-height: 96px;
            resize: vertical
        }

        .select-wrap {
            position: relative
        }

        .select-wrap select {
            appearance: none;
            padding-right: 42px;
            font-weight: 600
        }

        .select-wrap::after {
            content: "";
            position: absolute;
            right: 12px;
            top: 50%;
            width: 10px;
            height: 10px;
            transform: translateY(-60%) rotate(45deg);
            border-right: 2px solid var(--hint);
            border-bottom: 2px solid var(--hint);
            pointer-events: none;
            transition: transform .2s, border-color .2s;
        }

        .select-wrap:has(select:focus)::after {
            transform: translateY(-40%) rotate(225deg);
            border-color: var(--link)
        }

        .chip {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .08);
            font-size: 12px;
            color: var(--hint);
            gap: 6px
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 14px
        }

        .btn {
            appearance: none;
            border: 0;
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 700;
            cursor: pointer;
            background: var(--btn);
            color: var(--btn-text);
            box-shadow: 0 8px 22px rgba(46, 166, 255, .35);
            transition: transform .12s ease, filter .2s ease;
            box-sizing: border-box;
            width: 100%;
        }

        .btn:active {
            transform: translateY(1px) scale(.99);
            filter: brightness(.95)
        }

        .status {
            margin-top: 12px;
            font-weight: 600
        }

        .ok {
            color: var(--ok)
        }

        .err {
            color: var(--danger)
        }

        @media (max-width:700px) {
            .row {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Кабинет заявок</h1>
        <p class="hint">Выберите тип запроса и заполните форму</p>

        <!-- Выпадающий список типа заявки -->
        <div class="field">
            <label for="type">Тип запроса</label>
            <div class="select-wrap">
                <select id="type" name="type">
                    <option value="new_order">🆕 Новый клиент — Новый заказ</option>
                    <option value="old_order">🔁 Действующий клиент — Заказ</option>
                    <option value="request_document">📄 Запрос документа</option>
                    <option value="request_pdf">🖨️ Запрос PDF</option>
                </select>
            </div>
        </div>

        <form id="orderForm" autocomplete="on" novalidate>
            <!-- Блок: новый/старый заказ -->
            <section id="sec-order" class="section">
                <div class="chip">Детали заказа</div>
                <div class="row" style="margin-top:10px">
                    <div class="field">
                        <label>Название франшизы</label>
                        <input name="franchise" required />
                    </div>
                    <div class="field">
                        <label>Название организации</label>
                        <input name="organization" required />
                    </div>
                </div>
                <div class="row">
                    <div class="field">
                        <label>Стоимость лида</label>
                        <input name="cost" type="number" step="0.01" min="0" required />
                    </div>
                    <div class="field">
                        <label>Количество лидов</label>
                        <input name="leads" type="number" min="1" required />
                    </div>
                </div>
                <div class="field">
                    <label>Комментарий</label>
                    <textarea name="comment" placeholder="Дополнительные детали (необязательно)"></textarea>
                </div>

                <!-- Загрузка файла: обязательно для «Новый клиент — Новый заказ» -->
                <div class="field">
                    <label>Приложить документ</label>
                    <input name="document" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" />
                    <small class="hint">PDF, DOC/DOCX, XLS/XLSX, PNG, JPG (желательно &lt; 20 МБ)</small>
                </div>
            </section>

            <!-- Блок: запрос документа/PDF -->
            <section id="sec-doc" class="section hidden">
                <div class="chip">Запрос документа</div>
                <div class="row" style="margin-top:10px">
                    <div class="field">
                        <label>Название организации</label>
                        <input name="organization2" />
                    </div>
                    <div class="field">
                        <label>Номер заказа</label>
                        <input name="order_number" />
                    </div>
                </div>
                <div class="row">
                    <div class="field">
                        <label>Дата заказа</label>
                        <input name="order_date" type="date" />
                    </div>
                    <div class="field">
                        <label>Итоговая сумма</label>
                        <input name="total_cost" type="number" step="0.01" min="0" />
                    </div>
                </div>
                <div class="field">
                    <label>Комментарий</label>
                    <textarea name="comment2" placeholder="Дополнительные детали (необязательно)"></textarea>
                </div>
            </section>

            <div class="actions">
                <button class="btn" type="submit">Отправить запрос</button>
            </div>

            <div id="status" class="status"></div>
        </form>
    </div>

    <script>
        const tg = window.Telegram?.WebApp;
        tg?.expand();
        const $ = (s) => document.querySelector(s);
        const form = $('#orderForm'),
            statusEl = $('#status');
        const typeSel = $('#type'),
            orderSec = $('#sec-order'),
            docSec = $('#sec-doc');

        function setSection() {
            const val = typeSel.value;
            if (val === 'new_order' || val === 'old_order') {
                orderSec.classList.remove('hidden');
                docSec.classList.add('hidden');
            } else {
                orderSec.classList.add('hidden');
                docSec.classList.remove('hidden');
            }
        }
        typeSel.addEventListener('change', setSection);
        setSection();

        function ok(msg) {
            statusEl.textContent = msg;
            statusEl.className = 'status ok';
            tg?.HapticFeedback?.notificationOccurred('success');
        }

        function err(msg) {
            statusEl.textContent = msg;
            statusEl.className = 'status err';
            tg?.HapticFeedback?.notificationOccurred('error');
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            statusEl.textContent = '';

            const type = typeSel.value;
            const manager_id = tg?.initDataUnsafe?.user?.id || 0;

            // Для нового клиента — напомним про файл (мягкая проверка на фронте)
            if (type === 'new_order' && !form.document.files[0]) {
                err('Для нового клиента нужно приложить документ');
                return;
            }

            const fd = new FormData();
            fd.append('type', type);
            fd.append('manager_id', manager_id);

            if (type === 'new_order' || type === 'old_order') {
                fd.append('franchise', form.franchise.value.trim());
                fd.append('organization', form.organization.value.trim());
                fd.append('cost', form.cost.value || '0');
                fd.append('leads', form.leads.value || '0');
                fd.append('comment', form.comment.value.trim());
                if (form.document.files[0]) fd.append('document', form.document.files[0]);
            } else {
                fd.append('organization', (form.organization2.value || '').trim());
                fd.append('order_number', (form.order_number.value || '').trim());
                fd.append('order_date', form.order_date.value || '');
                fd.append('total_cost', form.total_cost.value || '0');
                fd.append('comment', (form.comment2.value || '').trim());
            }

            try {
                const res = await fetch('/create-order', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();
                if (json.ok) {
                    ok('Заявка №' + json.order_id + ' отправлена ✅');
                    form.reset();
                    setSection();
                } else {
                    err(json.error || 'Ошибка');
                }
            } catch (e) {
                err('Сетевая ошибка');
            }
        });
    </script>
</body>

</html>