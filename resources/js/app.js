import './bootstrap';
import Search from './live-search';
import Chat from './chat';
// import Profile from './profile';


// only instantiate live search feature if there's a search icon element
if (document.querySelector(".header-search-icon")) {
    new Search();
}

if (document.querySelector(".header-chat-icon")) {
    new Chat();
}

// if (document.querySelector(".profile-nav")) {
//     new Profile();
// }

