import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// ═══════════════════════════════════════════
// PrimeVue Core
// ═══════════════════════════════════════════
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';


// ═══════════════════════════════════════════
// PrimeVue Components
// ═══════════════════════════════════════════
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';
import InputOtp from 'primevue/inputotp';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import { Toast } from 'primevue';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import { InputNumber } from 'primevue';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Card from 'primevue/card';
import ProgressSpinner from 'primevue/progressspinner';
import ProgressBar from 'primevue/progressbar';
import InputIcon from 'primevue/inputicon';
import IconField from 'primevue/iconfield';
import Tooltip from 'primevue/tooltip';
import Chart from 'primevue/chart';
import SelectButton from 'primevue/selectbutton';
import FileUpload from 'primevue/fileupload';
import TreeTable from 'primevue/treetable';
import Timeline from 'primevue/timeline';
import TreeSelect from 'primevue/treeselect';
import Editor from 'primevue/editor';
import OverlayPanel from 'primevue/overlaypanel';       // ✅ جدید
import Avatar from 'primevue/avatar';                   // ✅ جدید
import Badge from 'primevue/badge';                     // ✅ جدید
import Divider from 'primevue/divider';                 // ✅ جدید
import ScrollTop from 'primevue/scrolltop';             // ✅ جدید
import MultiSelect from 'primevue/multiselect';
import AutoComplete from 'primevue/autocomplete';
import ToggleSwitch from 'primevue/toggleswitch';
import AccordionTab from 'primevue/accordiontab';
import Accordion from 'primevue/accordion';
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import StatCard from '@/components/contract/StatCard.vue';
import  AmountDisplay from '@/components/contract/AmountDisplay.vue';
import EmptyState from '@/components/contract/EmptyState.vue';
import ContractStatusBadge from '@/components/contract/ContractStatusBadge.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'


// ═══════════════════════════════════════════
// Persian Date Picker
// ═══════════════════════════════════════════
import Vue3PersianDatetimePicker from 'vue3-persian-datetime-picker';

// ═══════════════════════════════════════════
// Styles
// ═══════════════════════════════════════════
import './assets/main.css';
import 'primeicons/primeicons.css';
import './assets/fonts.css';

// ═══════════════════════════════════════════
// App Setup
// ═══════════════════════════════════════════
const app = createApp(App);

app.use(createPinia());
app.use(router);

// PrimeVue Config
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.dark',
        }
    }
});

// PrimeVue Services
app.use(ToastService);
app.use(ConfirmationService);

// ═══════════════════════════════════════════
// Global Components
// ═══════════════════════════════════════════
app.component('Toast', Toast);
app.component('DataTable', DataTable);
app.component('Column', Column);
app.component('Button', Button);
app.component('Message', Message);
app.component('InputOtp', InputOtp);
app.component('Password', Password);
app.component('InputText', InputText);
app.component('Dialog', Dialog);
app.component('ConfirmDialog', ConfirmDialog);
app.component('Tag', Tag);
app.component('InputNumber', InputNumber);
app.component('Select', Select);
app.component('Textarea', Textarea);
app.component('DatePicker', Vue3PersianDatetimePicker);
app.component('Checkbox', Checkbox);
app.component('TabView', TabView);
app.component('TabPanel', TabPanel);
app.component('Card', Card);
app.component('Chart', Chart);
app.component('ProgressSpinner', ProgressSpinner);
app.component('ProgressBar', ProgressBar);
app.component('InputIcon', InputIcon);
app.component('IconField', IconField);
app.component('SelectButton', SelectButton);
app.component('FileUpload', FileUpload);
app.component('TreeTable', TreeTable);
app.component('Timeline', Timeline);
app.component('TreeSelect', TreeSelect);
app.component('Editor', Editor);
app.component('OverlayPanel', OverlayPanel);
app.component('Avatar', Avatar)
app.component('Badge', Badge);
app.component('Divider', Divider);
app.component('ScrollTop', ScrollTop);
app.component('MultiSelect', MultiSelect);
app.component('AutoComplete', AutoComplete);
app.component('ToggleSwitch', ToggleSwitch);
app.component('AccordionTab', AccordionTab);
app.component('Accordion', Accordion);
app.component('StatCard', StatCard);
app.component('AmountDisplay', AmountDisplay);
app.component('EmptyState', EmptyState);
app.component('ContractStatusBadge', ContractStatusBadge);
app.component('DateDisplay', DateDisplay);

// ═══════════════════════════════════════════
// Directives
// ═══════════════════════════════════════════
app.directive('tooltip', Tooltip);






import {
    LayoutDashboard, Users, ShieldCheck, Wallet, FileText, SquareStack, Clock,
    FolderKanban, Handshake, Warehouse, Package, FolderTree, ArrowLeftRight,
    BarChart3, TrendingDown, UserCog, CalendarCheck, Building2, LibraryBig,
    BookOpen, Bookmark, Book, BookmarkCheck, MessageSquareWarning, PlusCircle,
    ShieldAlert, List, Network, FileSignature, Inbox,User,Activity, AlertTriangle, Eye, Mail, Calendar, Phone, CreditCard,
    Briefcase, Lock, Shield, Settings, Bell, Search, X, Check, Plus, Edit, Trash2,
    Save,Filter,Download,Upload,RefreshCw,Info,HelpCircle,LogOut, ChevronDown,
    ChevronLeft, ChevronRight, ArrowLeft, ArrowRight,UserPlus,UserCheck
} from 'lucide-vue-next'



// آیکون‌ها را در یک آبجکت قرار می‌دهیم تا از تکرار کد جلوگیری شود
const lucideIcons = {
    LayoutDashboard, Users, ShieldCheck, Wallet, FileText, SquareStack, Clock,
    FolderKanban, Handshake, Warehouse, Package, FolderTree, ArrowLeftRight,
    BarChart3, TrendingDown, UserCog, CalendarCheck, Building2, LibraryBig,
    BookOpen, Bookmark, Book, BookmarkCheck, MessageSquareWarning, PlusCircle,
    ShieldAlert, List, Network, FileSignature, Inbox,User,Activity, AlertTriangle, Eye, Mail, Calendar, Phone, CreditCard,
    Briefcase, Lock, Shield, Settings, Bell, Search, X, Check, Plus, Edit, Trash2,
    Save,Filter,Download,Upload,RefreshCw,Info,HelpCircle,LogOut, ChevronDown,
    ChevronLeft, ChevronRight, ArrowLeft, ArrowRight,UserPlus,UserCheck
}


// حلقه برای ثبت تمام آیکون‌ها به صورت گلوبال در اپلیکیشن
for (const [name, component] of Object.entries(lucideIcons)) {
    app.component(name, component)
}

// ═══════════════════════════════════════════
// Mount
// ═══════════════════════════════════════════
app.mount('#app');


