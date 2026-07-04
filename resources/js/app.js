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
    // Колонка канбан-доски: drag&drop карточек между колонками своей строки.
    // После дропа карточка возвращается на место, а сервер перерисовывает
    // доску — единственный источник правды о статусе.
    // -----------------------------------------------------------------------
    window.Alpine.data('kanbanColumn', () => ({
        sortable: null,

        init() {
            this.sortable = new Sortable(this.$el, {
                group: this.$el.dataset.rowKey,
                animation: 150,
                ghostClass: 'kanban-ghost',
                dragClass: 'kanban-dragging',
                onEnd: (evt) => {
                    if (evt.to === evt.from) {
                        return;
                    }

                    const taskId = Number(evt.item.dataset.taskId);
                    const toStatusId = Number(evt.to.dataset.statusId);

                    // Возвращаем DOM в исходное состояние — Livewire сам
                    // перерисует доску после ответа сервера
                    const reference = evt.from.children[evt.oldIndex] ?? null;
                    evt.from.insertBefore(evt.item, reference);

                    this.$wire.moveCard(taskId, toStatusId);
                },
            });
        },

        destroy() {
            this.sortable?.destroy();
        },
    }));

    // -----------------------------------------------------------------------
    // Live-обновление доски: подписка на канал проекта в Centrifugo
    // -----------------------------------------------------------------------
    window.Alpine.data('boardChannel', () => ({
        subscription: null,

        init() {
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

        destroy() {
            this.subscription?.unsubscribe();
        },
    }));
});
