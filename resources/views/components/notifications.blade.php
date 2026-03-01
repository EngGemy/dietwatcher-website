{{-- Toast Notifications --}}
<div
    x-data="{
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @notify.window="add($event.detail.message ?? $event.detail[0] ?? '', $event.detail.type ?? $event.detail[1] ?? 'success')"
    class="fixed top-6 z-[9999] flex flex-col gap-3 pointer-events-none"
    :class="document.dir === 'rtl' ? 'left-6' : 'right-6'"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
            class="toast-notification pointer-events-auto"
            :class="{
                'toast--success': toast.type === 'success',
                'toast--error': toast.type === 'error',
                'toast--info': toast.type === 'info'
            }"
        >
            {{-- Icon --}}
            <div class="toast-icon-wrap"
                 :class="{
                     'toast-icon--success': toast.type === 'success',
                     'toast-icon--error': toast.type === 'error',
                     'toast-icon--info': toast.type === 'info'
                 }">
                <template x-if="toast.type === 'success'">
                    <svg style="width:16px;height:16px;color:#3fb536" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg style="width:16px;height:16px;color:#ff707a" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </template>
                <template x-if="toast.type === 'info'">
                    <svg style="width:16px;height:16px;color:#279ff9" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                </template>
            </div>

            {{-- Message --}}
            <span x-text="toast.message" class="toast-message"></span>

            {{-- Close --}}
            <button @click="remove(toast.id)" class="toast-close">
                <svg style="width:14px;height:14px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>

<style>
.toast-notification {
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 12px;
    padding: 14px 20px;
    min-width: 280px;
    max-width: 400px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    font-family: 'Instrument Sans', 'Almarai', ui-sans-serif, system-ui, sans-serif;
    border: 1px solid;
}
.toast-icon-wrap {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
}
.toast-message {
    flex: 1;
    font-size: 0.875rem;
    font-weight: 600;
    color: #2e2e30;
    line-height: 1.4;
}
.toast-close {
    flex-shrink: 0;
    opacity: 0.4;
    transition: opacity 0.2s;
    color: #2e2e30;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
}
.toast-close:hover {
    opacity: 1;
}

/* Success - Brand Green */
.toast--success {
    background-color: #f0faf0;
    border-color: rgba(63,181,54,0.25);
}
.toast-icon--success {
    background-color: rgba(63,181,54,0.12);
}

/* Error - Brand Red */
.toast--error {
    background-color: #fff5f5;
    border-color: rgba(255,112,122,0.25);
}
.toast-icon--error {
    background-color: rgba(255,112,122,0.12);
}

/* Info - Brand Blue */
.toast--info {
    background-color: #f0f8ff;
    border-color: rgba(39,159,249,0.25);
}
.toast-icon--info {
    background-color: rgba(39,159,249,0.12);
}
</style>
