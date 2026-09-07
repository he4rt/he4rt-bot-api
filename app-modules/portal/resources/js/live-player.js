import Hls from 'hls.js';
import { echo } from './echo';

let hls = null;
let mountedVideo = null;
let subscribedChannel = null;

const teardown = () => {
    if (hls) {
        hls.destroy();
        hls = null;
    }
    mountedVideo = null;
};

const showWaiting = () => {
    teardown();
    document.querySelector('[data-live-waiting]')?.classList.remove('hidden');
    document.querySelector('video[data-live-player]')?.classList.add('hidden');
};

const mountPlayer = () => {
    const video = document.querySelector('video[data-live-player]');
    if (!video || mountedVideo === video) return;
    teardown();
    mountedVideo = video;
    video.classList.remove('hidden');
    document.querySelector('[data-live-waiting]')?.classList.add('hidden');
    const src = video.dataset.hlsUrl;
    if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = src;
        video.play().catch(() => {});
        return;
    }
    if (Hls.isSupported()) {
        hls = new Hls();
        hls.on(Hls.Events.ERROR, (_event, data) => {
            if (data.fatal) showWaiting();
        });
        hls.loadSource(src);
        hls.attachMedia(video);
    }
};

const appendChatMessage = (message) => {
    const list = document.querySelector('[data-chat-list]');
    if (!list || list.querySelector(`[data-message-id="${CSS.escape(message.id)}"]`)) return;
    const item = document.createElement('div');
    item.dataset.chatMessage = '';
    item.dataset.messageId = message.id;
    item.className = 'flex items-start gap-2';
    const avatar = document.createElement('img');
    avatar.src = message.authorAvatarUrl;
    avatar.alt = '';
    avatar.className = 'h-6 w-6 rounded-full';
    const text = document.createElement('p');
    text.className = 'text-text-medium text-sm break-words';
    const author = document.createElement('span');
    author.className = 'text-text-high font-semibold';
    author.textContent = message.authorUsername;
    text.append(author, ` ${message.content}`);
    item.append(avatar, text);
    list.append(item);
    list.scrollTop = list.scrollHeight;
};

const subscribe = () => {
    const holder = document.querySelector('[data-live-channel]');
    if (!holder || subscribedChannel === holder.dataset.liveChannel) return;
    subscribedChannel = holder.dataset.liveChannel;
    echo.channel(`live.${subscribedChannel}`)
        .listen('.ChatMessageSent', (event) => appendChatMessage(event.message))
        .listen('.ChatMessageDeleted', (event) => {
            document.querySelector(`[data-message-id="${CSS.escape(event.messageId)}"]`)?.remove();
        })
        .listen('.LiveStarted', () => {
            if (window.Livewire) {
                window.Livewire.all().forEach((component) => component.$wire.$refresh());
                return;
            }
            mountPlayer();
        })
        .listen('.LiveEnded', () => showWaiting());
};

const boot = () => {
    subscribe();
    mountPlayer();
};

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:init', () => {
    Livewire.hook('morphed', boot);
});
