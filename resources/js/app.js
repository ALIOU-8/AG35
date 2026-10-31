import './echo';
import Alpine from 'alpinejs';
import { registerShell } from './components/shell';
import { postCard, postComposer } from './components/feed';
import chat from './components/chat';
import notificationBell from './components/notifications';
import { jitsiRoom, meetingRoom } from './components/meeting';

window.Alpine = Alpine;

registerShell(Alpine);

Alpine.data('postComposer', postComposer);
Alpine.data('postCard', postCard);
Alpine.data('chat', chat);
Alpine.data('notificationBell', notificationBell);
Alpine.data('meetingRoom', meetingRoom);
Alpine.data('jitsiRoom', jitsiRoom);

Alpine.start();
