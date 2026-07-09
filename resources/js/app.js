import './bootstrap';
import Sortable from 'sortablejs';
import { Centrifuge } from 'centrifuge';

// ---------------------------------------------------------------------------
// Centrifugo: одно соединение на страницу, подписки создают компоненты досок
// ---------------------------------------------------------------------------
let centrifuge = null;

function getCentrifuge() {
    if (centrifuge) {
        return centrifuge;
    }

    const token = document.querySelector('meta[name="centrifugo-token"]')?.content;

    if (!token) {
        return null;
    }

    const wsUrl = `${window.location.origin.replace(/^http/, 'ws')}/connection/websocket`;

    centrifuge = new Centrifuge(wsUrl, { token });
    centrifuge.connect();

    return centrifuge;
}

document.addEventListener('livewire:init', () => {
    // -----------------------------------------------------------------------
    // Тонкий индикатор загрузки сверху: виден, пока идут Livewire-запросы
    // -----------------------------------------------------------------------
    const progressBar = document.createElement('div');
    progressBar.className = 'lw-progress';
    document.body.appendChild(progressBar);

    let activeRequests = 0;

    window.Livewire.hook('request', ({ respond }) => {
        activeRequests++;
        progressBar.classList.add('active');

        respond(() => {
            activeRequests = Math.max(0, activeRequests - 1);

            if (activeRequests === 0) {
                progressBar.classList.remove('active');
            }
        });
    });

    // -----------------------------------------------------------------------
    // Колонка канбан-доски: drag&drop карточек между колонками своей строки.
    // После дропа карточка возвращается на место, а сервер перерисовывает
    // доску — единственный источник правды о статусе.
    // -----------------------------------------------------------------------
    window.Alpine.data('kanbanColumn', () => ({
        sortable: null,

        init() {
            this.sortable = new Sortable(this.$el, {
                // Одна группа на всю доску: карточки можно таскать и между
                // колонками (смена статуса), и между строками (смена родителя)
                group: 'board',
                animation: 150,
                // Вместо нативного полупрозрачного снимка браузера за курсором
                // следует клон карточки, который мы стилизуем сами (.kanban-dragging)
                forceFallback: true,
                fallbackOnBody: true,
                fallbackTolerance: 4,
                ghostClass: 'kanban-ghost',
                dragClass: 'kanban-dragging',
                onStart: () => document.body.classList.add('kanban-grabbing'),
                onEnd: (evt) => {
                    document.body.classList.remove('kanban-grabbing');

                    const onFail = () => {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: 'Не удалось сохранить перемещение — доска обновлена.', type: 'error' },
                        }));
                        this.$wire.$refresh();
                    };

                    // Итоговый вертикальный порядок целевой ячейки — сервер
                    // сохранит его в board_order
                    const orderedIds = Array.from(evt.to.children)
                        .filter((el) => el.dataset.taskId)
                        .map((el) => Number(el.dataset.taskId));

                    if (evt.to === evt.from) {
                        // Перестановка внутри ячейки: меняется только порядок
                        if (evt.oldIndex !== evt.newIndex) {
                            this.$wire.reorderCell(orderedIds).catch(onFail);
                        }

                        return;
                    }

                    const taskId = Number(evt.item.dataset.taskId);
                    const toStatusId = Number(evt.to.dataset.statusId);
                    // Строка задаёт родителя; на плоской доске задач
                    // родителя не трогаем (parentScope отсутствует)
                    const applyParent = evt.to.dataset.parentScope === 'row';
                    const parentId = Number(evt.to.dataset.parentId || 0);

                    // Оптимистичное перемещение: карточка остаётся там, куда её
                    // бросили. Сервер — источник правды: после ответа morph либо
                    // подтвердит позицию, либо вернёт карточку назад (при ошибке
                    // придёт тост и старая раскладка). Если сам запрос упал —
                    // принудительно перечитываем доску, чтобы не разъехаться.
                    this.$wire.moveCard(taskId, toStatusId, parentId, applyParent, orderedIds)
                        .catch(onFail);
                },
            });
        },

        destroy() {
            this.sortable?.destroy();
        },
    }));

    // -----------------------------------------------------------------------
    // @-упоминания: автокомплит юзеров и задач в textarea.
    // Оборачивающий див содержит textarea (x-ref="area") и дропдаун;
    // подсказки запрашиваются у Livewire-компонента ($wire.searchMentions)
    // -----------------------------------------------------------------------
    window.Alpine.data('mentionBox', () => ({
        open: false,
        items: [],
        active: 0,
        matchStart: 0,
        matchEnd: 0,

        async onInput() {
            const el = this.$refs.area;
            const upToCaret = el.value.slice(0, el.selectionStart);
            // «@» и часть логина/номера сразу перед кареткой
            const match = upToCaret.match(/@([\w.\-]*)$/);

            if (!match) {
                this.open = false;

                return;
            }

            this.matchStart = el.selectionStart - match[0].length;
            this.matchEnd = el.selectionStart;

            try {
                this.items = await this.$wire.searchMentions(match[1]);
            } catch {
                this.items = [];
            }

            this.active = 0;
            this.open = this.items.length > 0;
        },

        pick(item) {
            const el = this.$refs.area;
            // Юзер вставляется как @логин, задача — как номер (KEY-123)
            const insert = item.kind === 'user' ? `@${item.value}` : item.value;

            el.value = el.value.slice(0, this.matchStart) + insert + ' ' + el.value.slice(this.matchEnd);

            const caret = this.matchStart + insert.length + 1;
            el.setSelectionRange(caret, caret);
            // Синхронизирует wire:model с новым значением
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.focus();
            this.open = false;
        },

        onKeydown(event) {
            if (!this.open) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.active = (this.active + 1) % this.items.length;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.active = (this.active - 1 + this.items.length) % this.items.length;
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                this.pick(this.items[this.active]);
            } else if (event.key === 'Escape') {
                // Дропдаун закрывается, модалка остаётся открытой
                event.stopPropagation();
                this.open = false;
            }
        },
    }));

    // -----------------------------------------------------------------------
    // Умный фильтр задач: автокомплит полей («статус:», «исполнитель:»…) и их
    // значений. Значения зашиты в data-meta (справочники проекта маленькие),
    // так что подсказки полностью клиентские — работает и в слоте шапки,
    // где $wire недоступен. Значение поля шлётся Livewire-событием.
    // -----------------------------------------------------------------------
    window.Alpine.data('filterBox', () => ({
        open: false,
        items: [],
        active: 0,
        meta: {},
        event: 'board-filter',
        debounceTimer: null,

        init() {
            this.meta = JSON.parse(this.$el.dataset.meta || '{}');
            this.event = this.$el.dataset.event || 'board-filter';
        },

        emit() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                window.Livewire.dispatch(this.event, { value: this.$refs.input.value });
            }, 400);
        },

        onInput() {
            this.emit();
            this.suggest();
        },

        suggest() {
            const el = this.$refs.input;
            const before = el.value.slice(0, el.selectionStart);
            const fields = Object.keys(this.meta);

            // Каретка внутри значения токена: «поле: недопеча…»
            const valueMatch = before.match(
                new RegExp(`(?:^|\\s)(${fields.join('|')})\\s*:\\s*("[^"]*|«[^»]*|[^\\s]*)$`, 'i'),
            );

            if (valueMatch) {
                const field = valueMatch[1].toLowerCase();
                const typed = valueMatch[2].replace(/^["«]/, '').toLowerCase();

                this.items = (this.meta[field] || [])
                    .filter((v) => v.toLowerCase().includes(typed))
                    .slice(0, 8)
                    .map((v) => ({
                        // Значения с пробелами вставляются в кавычках
                        insert: (/\s/.test(v) ? `"${v}"` : v) + ' ',
                        label: v,
                        hint: field,
                        start: before.length - valueMatch[2].length,
                    }));
            } else {
                // Начало слова → подсказываем сами поля
                const wordMatch = before.match(/(?:^|\s)([а-яёa-z]*)$/i);
                const typed = wordMatch ? wordMatch[1].toLowerCase() : null;

                this.items = typed === null ? [] : fields
                    .filter((f) => f.startsWith(typed))
                    .map((f) => ({
                        insert: `${f}: `,
                        label: `${f}:`,
                        hint: 'поле',
                        start: before.length - typed.length,
                    }));
            }

            this.active = 0;
            this.open = this.items.length > 0;
        },

        pick(item) {
            const el = this.$refs.input;
            const tail = el.value.slice(el.selectionStart);

            el.value = el.value.slice(0, item.start) + item.insert + tail;

            const caret = item.start + item.insert.length;
            el.focus();
            el.setSelectionRange(caret, caret);
            this.emit();
            this.suggest();
        },

        onKeydown(event) {
            if (!this.open) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.active = (this.active + 1) % this.items.length;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.active = (this.active - 1 + this.items.length) % this.items.length;
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                this.pick(this.items[this.active]);
            } else if (event.key === 'Escape') {
                event.stopPropagation();
                this.open = false;
            }
        },
    }));

    // -----------------------------------------------------------------------
    // Доска: live-обновления (Centrifugo) + клиентский коллапс строк/колонок
    // (без запросов на сервер, состояние переживает перезагрузку в localStorage)
    // -----------------------------------------------------------------------
    window.Alpine.data('boardChannel', () => ({
        subscription: null,
        collapsedCols: [],
        collapsedRows: [],
        statusIds: [],
        storageKey: '',

        init() {
            // --- Коллапс ---
            this.statusIds = JSON.parse(this.$el.dataset.statusIds || '[]');
            this.storageKey = `board-collapse:${this.$el.dataset.channel}:${this.$el.dataset.tab}`;

            try {
                const saved = JSON.parse(localStorage.getItem(this.storageKey) || '{}');
                this.collapsedCols = saved.cols ?? [];
                this.collapsedRows = saved.rows ?? [];
            } catch {
                // повреждённое хранилище — начинаем с чистого состояния
            }

            // --- Centrifugo ---
            const client = getCentrifuge();
            const channel = this.$el.dataset.channel;

            if (!client || !channel) {
                return;
            }

            this.subscription = client.getSubscription(channel)
                ?? client.newSubscription(channel);

            this.subscription.on('publication', (ctx) => {
                window.Livewire.dispatch('board-remote-update', { payload: ctx.data });
            });

            this.subscription.subscribe();
        },

        persistCollapse() {
            localStorage.setItem(this.storageKey, JSON.stringify({
                cols: this.collapsedCols,
                rows: this.collapsedRows,
            }));
        },

        toggleCol(id) {
            this.collapsedCols = this.isColCollapsed(id)
                ? this.collapsedCols.filter((x) => x !== id)
                : [...this.collapsedCols, id];
            this.persistCollapse();
        },

        toggleRow(key) {
            this.collapsedRows = this.isRowCollapsed(key)
                ? this.collapsedRows.filter((x) => x !== key)
                : [...this.collapsedRows, key];
            this.persistCollapse();
        },

        isColCollapsed(id) {
            return this.collapsedCols.includes(id);
        },

        isRowCollapsed(key) {
            return this.collapsedRows.includes(key);
        },

        get gridStyle() {
            const template = this.statusIds
                .map((id) => (this.isColCollapsed(id) ? '64px' : 'minmax(245px, 1fr)'))
                .join(' ');

            return `grid-template-columns: ${template};`;
        },

        destroy() {
            this.subscription?.unsubscribe();
        },
    }));
});
