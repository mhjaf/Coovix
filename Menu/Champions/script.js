/**
 * ============================================================================
 * CHAMPIONS RESTAURANT WEBSITE APPLICATION - ALL ORIENTATIONS SUPPORTED
 * ============================================================================
 * 
 * @fileoverview Multi-language restaurant website with all orientations supported
 * @author Champions Restaurant Development Team
 * @version 2.3.4
 * @since 2024
 * 
 * @description Features:
 *              - Multi-language support (English, Kurdish, Arabic)
 *              - Direct language selection buttons (no dropdown)
 *              - Complete menu with all beverage categories
 *              - Responsive navigation and mobile menu
 *              - Dynamic menu item rendering
 *              - Modal-based item details with drag-to-close functionality
 *              - Category-based navigation
 *              - Feedback page functionality
 *              - Session management
 *              - ALL ORIENTATIONS SUPPORTED (Portrait & Landscape)
 *              - Navigation Language Buttons Support
 *              - FIXED: Language selection now properly shows website content
 * 
 * @requires DOM Level 2 Events
 * @requires IntersectionObserver API
 * @requires SessionStorage API
 * ============================================================================
 */

'use strict';

/**
 * ============================================================================
 * APPLICATION CONFIGURATION
 * ============================================================================
 */

const APP_CONFIG = {
    VERSION: '2.3.4',
    DEFAULT_LANGUAGE: 'en',
    SUPPORTED_LANGUAGES: ['en', 'ku', 'ar'],
    NAVIGATION_DELAY: 650,
    SCROLL_OFFSET: 20,
    MOBILE_BREAKPOINT: 768,
    ORIENTATION_CHANGE_DELAY: 100,
    AUTO_SELECT_DEFAULT: false, // Require manual language selection
    FORCE_LANDSCAPE: false, // DISABLED: Allow all orientations
    FEEDBACK_PAGES: {
        'en': 'feedback.html',
        'ku': 'feedback-ku.html',
        'ar': 'feedback-ar.html'
    },
    // Modal drag configuration
    MODAL_DRAG: {
        CLOSE_THRESHOLD: 150, // pixels to drag down before closing
        VELOCITY_THRESHOLD: 0.5, // velocity threshold for quick swipes
        OPACITY_MIN: 0.3, // minimum opacity during drag
        ANIMATION_DURATION: 300 // ms for animations
    }
};

// Images used to point at a hosting-account-specific absolute directory.
// Keep assets relative to this project so they work locally and at any deploy path.
const LEGACY_ASSET_PREFIX = '/20259597/menu/champions_saladbar&cafe/';

function normalizeAssetUrl(url) {
    if (typeof url !== 'string') {
        return url;
    }

    return url.startsWith(LEGACY_ASSET_PREFIX)
        ? url.slice(LEGACY_ASSET_PREFIX.length)
        : url;
}

const DOM_SELECTORS = {
    LANGUAGE_SELECTION_SCREEN: '#languageSelectionScreen',
    LANGUAGE_POPUP_OVERLAY: '#languagePopupOverlay',
    LANGUAGE_SELECT_BUTTONS: '.language-select-button', // Updated for direct buttons
    NAVBAR_LANGUAGE_BUTTON: '#navbarLanguageBtn',
    MOBILE_LANGUAGE_BUTTON: '#mobileLanguageBtn',
    MOBILE_MENU_TOGGLE: '#mobile-menu-toggle',
    MOBILE_MENU_OVERLAY: '#mobile-menu-overlay',
    MOBILE_MENU_CLOSE: '#mobileMenuClose',
    REVIEWS_BUTTON: '#reviewsBtn',
    FOOD_MODAL: '#foodModal',
    CATEGORY_BUTTONS: '.category-nav-button',
    CATEGORIES_GRID: '.categories-grid',
    MENU_SECTIONS: '.menu-section',
    LANGUAGE_OPTIONS: '.language-option',
    // New selectors for navigation language buttons
    DESKTOP_LANG_BUTTONS: '.lang-button',
    MOBILE_LANG_BUTTONS: '.mobile-lang-button'
};

const CSS_CLASSES = {
    HIDDEN: 'hidden',
    SHOW: 'show',
    ACTIVE: 'active',
    SELECTED: 'selected',
    MOBILE_HIDDEN: 'mobile-hidden',
    MOBILE_MENU_OPEN: 'mobile-menu-open',
    LANGUAGE_POPUP_OPEN: 'language-popup-open',
    USER_CLICKED: 'user-clicked',
    REVEALED: 'revealed',
    DRAGGING: 'dragging'
};

const STORAGE_KEYS = {
    LANGUAGE_SELECTED: 'languageSelected',
    SELECTED_LANGUAGE: 'selectedLanguage'
};

/**
 * ============================================================================
 * APPLICATION STATE MANAGEMENT
 * ============================================================================
 */

class ApplicationState {
    constructor() {
        this.menuData = {};
        this.translations = {};
        this.isLanguageSelectedInSession = false;
        this.savedScrollPosition = 0;
        this.isMobileMenuOpen = false;
        this.isLanguagePopupOpen = false;
        // Modal drag state
        this.modalDragState = {
            isDragging: false,
            startY: 0,
            currentY: 0,
            startTime: 0,
            lastY: 0,
            lastTime: 0,
            velocity: 0
        };
    }

    reset() {
        this.isLanguageSelectedInSession = false;
        this.savedScrollPosition = 0;
        this.isMobileMenuOpen = false;
        this.isLanguagePopupOpen = false;
        this.modalDragState = {
            isDragging: false,
            startY: 0,
            currentY: 0,
            startTime: 0,
            lastY: 0,
            lastTime: 0,
            velocity: 0
        };
    }
}

const appState = new ApplicationState();

/**
 * ============================================================================
 * LOGGING UTILITY
 * ============================================================================
 */

class Logger {
    static info(message, ...args) {
        console.log(`[INFO] ${message}`, ...args);
    }

    static warning(message, ...args) {
        console.warn(`[WARNING] ${message}`, ...args);
    }

    static error(message, ...args) {
        console.error(`[ERROR] ${message}`, ...args);
    }
}

/**
 * ============================================================================
 * TRANSLATION MANAGER
 * ============================================================================
 */

class TranslationManager {
    static getTranslationData() {
        return {
            en: {
                chooseLanguage: "Choose Your Language",
                selectLanguage: "Select Language",
                callUs: "Call Us",
                openHours: "Open Hours",
                visitUs: "Visit Us",
                hours: "11:00 AM - 12:00 PM",
                address: "Erbil City Center, Kurdistan",
                Salad: "SALAD",
                openBuffet: "OPEN BUFFET",
                dessert: "DESSERT",
                hotDrinks: "HOT DRINKS",
                icedCoffee: "ICED COFFEE",
                juices: "JUICES",
                smoothies: "SMOOTHIES",
                mojitos: "MOJITOS",
                frappuccino: "FRAPPUCCINO",
                milkshakes: "MILKSHAKES",
                softDrinks: "SOFT DRINKS",
                shishaItem: "English",
                shishaLemoMint: "Lemo Mint",
                shishaMintGum: "Mint Gum",
                shishaMelonGum: "Melon Gum",
                shishaDoubleApple: "Double Apple",
                shishaBaghdadi: "Baghdadi",
                shishaChampions: "Champions",
                shishaChampions2: "Champions 2",
                shishaFreshFruit: "Fresh Fruit",
                reviews: "Reviews",
                feedback: "Feedback",
                menuItems: {
                    //salad 
                    cacik:"CACIK",
                    cacikDesc:"CACIK",

                    arugulasalad:"ARUGULA SALAD",
                    arugulasaladDesc:"ARUGULA SALAD",

                    burhanisalad:"BURHANI SALAD",
                    burhanisaladDesc:"burhani salad",

                    caesarsalad:"CAESAR SALAD",
                    caesarsaladDesc:"caesar",

                    coleslaw:"COLESLAW",
                    coleslawDesc:"COLESLAW",

                    gavurdasalad:"GAVURDA SALAD",
                    gavurdasaladDesc:"GAVURDA SALAD",

                    greecesalad:"GREECE SALAD",
                    greecesaladDesc:"GREECE SALAD",

                    grilledcheese:"GRILLED HALLOUMI CHEESE",
                    grilledcheeseDecs:"GRILLED HALLOUMI CHEESE",
                    
                    

                    // Open Buffet
                    mixedSalad: "MIXED SALAD",
                    mixedSaladDesc: "Perfectly grilled chicken with herbs and spices",
                    chicken: "CHICKEN STEAK",
                    chickenDesc: "Perfectly grilled chicken with herbs and spices",
                    meat: "MEAT STEAK",
                    meatDesc: "Tender grilled meat with traditional seasoning",

                    MEATFAJITA:"MEAT FAJITA",
                    MEATFAJITADesc:"MEAT FAJITA",

                    chickenfajita:" CHICKEN FAJITA",
                    chickenfajitaDesc:"chicken fajita",

                    MEATISABELA:"MEAT ISABELA",
                    MEATISABELADesc:"MEAT ISABELA",

                    
                    CHICKENCURRY:"CHICKEN CURRY",
                    CHICKENCURRYDesc:"CHICKEN CURRY",
                   
                    fish: "salmon",
                    fishDesc: "Fresh grilled fish with lemon and herbs",

                    meatkofta: "MEAT KOFTA",
                    meatkoftaDesc:"efveverve",

                    salmon: "GRILLED SALMON",
                    salmonDesc: "Atlantic salmon grilled to perfection",

                    shrimp: "SHRIMP",
                    shrimpDesc: "Crispy falafel balls with traditional Middle Eastern spices",

                    //SANDWICH
                    chickensandwich:"CHICKEN SANDWICH",
                    chickensandwichDecs:"CHICKEN SANDWICH",

                    meatsandwich:"MEAT SANDWICH",
                    meatsandwichDesc:"MEAT SANDWICH",

                    meatburger:"MEAT BURGER",
                    meatburgerDesc:"meatburger",
                    
                    // Desserts
                    tiramisu: "TIRAMISU",
                    tiramisuDesc: "Traditional Italian dessert with coffee and mascarpone",

                    cheesecake: " PISTACHIO CHEESECAKE",
                    cheesecakeDesc: "Rich and creamy cheesecake with berry compote",

                    cheesecakestrawberry: "STRAWBERRY CHEESECAKE",
                    cheesecakestrawberryDesc: "biwbfiwbviwbvierbvir",

                    blackvalvet: "BLACK VALVET",
                    blackvalvetDesc:"ergferver",

                    dubaicake: "DUBAI CAKE",
                    dubaicakeDesc: "erfverf",

                    meites:"MEITES",
                    meitesDesc:"ergerg",

                    redvalvet:"RED VALVET",
                    redvalvetDesc: "wfrgerf",

                    sansebastian:"SAN SEBASTIAN",
                    sansebastianDesc:"wrfwrf",

                    croissantnormal:"CROISSANT NORMAL",
                    croissantnormalDesc:"rbrtgbrtg",

                    croissant:"CROISSANT CHAMPIONS",
                    croissantDesc:"CROISSANT",


                    croissantcheese:"CROISSANT CHEESE",
                    croissantcheeseDesc:"erfererer",

                    croissantnutella:"CROISSANT NUTELLA",
                    croissantnutellaDesc:"erwergfwerg",

                    appletart:"APPLE TART",
                    appletartDesc:"RFERFER",

                    minipancake:"MINI PANCAKE",
                     minipancakeDesc:"MINI PANCAKE",

                     kebabwaffle:"KEBAB WAFFLE",
                    kebabwaffleDesc:"KEBAB WAFFLE",


                    
                    // Hot Drinks
                    singleEspresso: "SINGLE ESPRESSO",
                    singleEspressoDesc: "Rich Italian espresso shot",
                    doubleEspresso: "DOUBLE ESPRESSO",
                    doubleEspressoDesc: "Double shot of rich Italian espresso",
                    cappuccino: "CAPPUCCINO",
                    cappuccinoDesc: "Perfect blend of espresso and steamed milk",
                    espressoAvocado: "ESPRESSO AVOCADO",
                    espressoAvocadoDesc: "Unique blend of espresso with avocado",
                    hotLatte: "HOT LATTE",
                    hotLatteDesc: "Smooth espresso with steamed milk",
                    americano: "AMERICANO",
                    americanoDesc: "Espresso with hot water",
                    lemonGreenTea: "LEMON GREEN TEA",
                    lemonGreenTeaDesc: "Refreshing green tea with fresh lemon",
                    darkChocolate: "DARK CHOCOLATE",
                    darkChocolateDesc: "Rich dark chocolate drink",
                    hotChocolate: "HOT CHOCOLATE",
                    hotChocolateDesc: "Creamy hot chocolate",
                    italianHotChocolate: "ITALIAN HOT CHOCOLATE",
                    italianHotChocolateDesc: "Thick Italian-style hot chocolate",
                    hotSpanishLatte: "SPANISH LATTE",
                    hotSpanishLatteDesc: "Spanish-style latte with condensed milk",
                    vanillaLatte: "VANILLA LATTE",
                    vanillaLatteDesc: "Smooth latte with vanilla syrup",
                    mochaLatte: "MOCHA LATTE",
                    mochaLatteDesc: "Coffee and chocolate blend",
                    turkishCoffee: "TURKISH COFFEE",
                    turkishCoffeeDesc: "Traditional Turkish coffee",
                    qazwanCoffee: "QAZWAN COFFEE",
                    qazwanCoffeeDesc: "Special Qazwan blend coffee",
                    filteredCoffee: "FILTERED COFFEE",
                    filteredCoffeeDesc: "Classic filtered coffee",
                    
                    // Iced Coffee
                    icedCaramelLatte: "ICED CARAMEL LATTE",
                    icedCaramelLatteDesc: "Iced latte with caramel syrup",
                    icedLatte: "ICED LATTE",
                    icedLatteDesc: "Cold espresso with chilled milk",
                    icedSpanishLatte: "ICED SPANISH LATTE",
                    icedSpanishLatteDesc: "Iced Spanish-style latte",
                    icedMochaLatte: "ICED MOCHA LATTE",
                    icedMochaLatteDesc: "Iced coffee and chocolate blend",
                    icedPistachioLatte: "ICED PISTACHIO LATTE",
                    icedPistachioLatteDesc: "Iced latte with pistachio flavor",
                    icedChocolate: "ICED CHOCOLATE",
                    icedChocolateDesc: "Cold chocolate drink",
                    icedDarkChocolate: "ICED DARK CHOCOLATE",
                    icedDarkChocolateDesc: "Cold dark chocolate drink",
                    icedAmericano: "ICED AMERICANO",
                    icedAmericanoDesc: "Espresso with cold water and ice",
                    icedCappuccino: "ICED CAPPUCCINO",
                    icedCappuccinoDesc: "Cold cappuccino with ice",
                    icedCoconut: "ICED COCONUT",
                    icedCoconutDesc: "Refreshing coconut drink",
                    
                    // Juices
                    championsJuice: "CHAMPIONS JUICE",
                    championsJuiceDesc: "Our signature fruit juice blend",
                    orangeJuice: "FRESH ORANGE JUICE",
                    orangeJuiceDesc: "Freshly squeezed orange juice",
                    lemonJuice: "FRESH LEMON JUICE",
                    lemonJuiceDesc: "Freshly squeezed lemon juice",
                    bananaMilk: "BANANA MILK",
                    bananaMilkDesc: "Creamy banana milk shake",
                    appleBanana: "APPLE BANANA",
                    appleBananaDesc: "Apple and banana juice blend",

                    pomegranatejuice: "POMEGRANTE JUICE",
                    pomegranatejuiceDesc: "Apple and banana juice blend",
                    
                    // Smoothies
                    championsSmootbie: "CHAMPIONS SMOOTHIE",
                    championsSmootbieDesc: "Our signature smoothie blend",
                    mangoSmoothie: "MANGO SMOOTHIE",
                    mangoSmoothieDesc: "Fresh mango smoothie",
                    strawberrySmoothie: "STRAWBERRY SMOOTHIE",
                    strawberrySmoothieDesc: "Fresh strawberry smoothie",
                    strawberrybananaSmoothie: "STRAWBERRY BANANA",
                    strawberrybananaSmoothieDesc: "Fresh strawberry smoothie",

                    orangeSmoothie: "ORANGE SMOOTHIE",
                    orangeSmoothieDesc: "Fresh orange smoothie",
                    pineappleStrawberry: "PINEAPPLE STRAWBERRY",
                    pineappleStrawberryDesc: "Tropical pineapple and strawberry blend",
                    lemonMint: "LEMON MINT",
                    lemonMintDesc: "Refreshing lemon mint smoothie",
                    
                    // Mojitos
                    strawberryMojito: "STRAWBERRY MOJITO",
                    strawberryMojitoDesc: "Fresh strawberry mojito",
                    blueSky: "BLUE SKY MOJITO",
                    blueSkyDesc: "Blue sky flavored mojito",
                    blueberryMojito: "BLUEBERRY MOJITO",
                    blueberryMojitoDesc: "Fresh blueberry mojito",
                    classicmojito:"CLASSIC MOJITO",
                    classicmojitoDesc:"erger",
                    passionFruit: "PASSION FRUIT MOJITO",
                    passionFruitDesc: "Passion fruit mojito",
                    mixedBerries: "MIXED BERRIES MOJITO",
                    mixedBerriesDesc: "Mixed berries mojito",
                    blueHawai: "BLUE HAWAI",
                    blueHawaiDesc: "Blue Hawaii tropical mojito",
                    coolLime: "COOL LIME",
                    coolLimeDesc: "Cool lime mojito",
                    
                    // Frappuccino
                    championsFrappe: "CHAMPIONS FRAPPE",
                    championsFrappeDesc: "Our signature frappuccino",
                    oreoFrappe: "OREO FRAPPE",
                    oreoFrappeDesc: "Oreo cookie frappuccino",
                    caramelFrappe: "CARAMEL FRAPPE",
                    caramelFrappeDesc: "Caramel frappuccino",
                    vanillaFrappe: "VANILLA FRAPPE",
                    vanillaFrappeDesc: "Vanilla frappuccino",
                    chocolateFrappe: "CHOCOLATE FRAPPE",
                    chocolateFrappeDesc: "Chocolate frappuccino",
                    lotusFrappe: "LOTUS FRAPPE",
                    lotusFrappeDesc: "Lotus cookie frappuccino",
                    
                    // Milkshakes
                    nutellaMilkshake: "NUTELLA MILKSHAKE",
                    nutellaMilkshakeDesc: "Creamy Nutella milkshake",

                    vanillaMilkshake: "VANILLA MILKSHAKE",
                    vanillaMilkshakeDesc: "Classic vanilla milkshake",
                    kinderMilkshake: "KINDER MILKSHAKE",
                    kinderMilkshakeDesc: "Kinder chocolate milkshake",

                    pistachioMilkshake: "PISTACHIO MILKSHAKE",
                    pistachioMilkshakeDesc: "Creamy pistachio milkshake",

                    lotusMilkshake: "LOTUS MILKSHAKE",
                    lotusMilkshakeDesc: "Lotus cookie milkshake",

                    strawberryMilkshake: "STRAWBERRY BANANA",
                    strawberryMilkshakeDesc: "Fresh strawberry milkshake",

                    strawberry: "STRAWBERRY MILKSHAKE",
                    strawberryDesc: "Strawberry and banana milkshake",

                    bananaMilkshake: "BANANA MILKSHAKE",
                    bananaMilkshakeDesc: "Creamy banana milkshake",

                    oreo:" OREO MILKSHAKE",
                    oreodesc:"Eerferfrtgrtg",
                    
                    // Soft Drinks
                    championsRedbull: "CHAMPIONS REDBULL",
                    championsRedbullDesc: "Our signature energy drink",
                    normalRedbull: "NORMAL REDBULL",
                    normalRedbullDesc: "Classic Red Bull energy drink",
                    redbullMexican: "MEXICAN REDBULL ",
                    redbullMexicanDesc: "Mexican style Red Bull",
                    cocaColaZero: "COCA COLA ZERO",
                    cocaColaZeroDesc: "Coca Cola Zero sugar",
                    sprite: "SPRITE",
                    spriteDesc: "Refreshing lemon-lime soda",
                    soda: "SODA",
                    sodaDesc: "Sparkling water",
                    water: "WATER",
                    waterDesc: "Fresh bottled water"
                },
                allRightsReserved: "All rights reserved",
                followUs: "Follow Us"
            },
            ku: {
                chooseLanguage: "زمانەکەت هەڵبژێرە",
                selectLanguage: "زمان هەڵبژێرە",
                callUs: "پەیوەندیمان پێوە بکە",
                openHours: "کاتی کارکردن",
                visitUs: "سەردانمان بکە",
                hours: "١١:٠٠ بەیانی - ١٢:٠٠ شەو",
                address: "هەولێر، رامی تاوەر",
                Salad:"زەڵاتە",
                openBuffet: "بووێی کراوە",
                dessert: "شیرینی",
                hotDrinks: "خواردنەوە گەرم",
                icedCoffee: "قاوەی سارد",
                juices: "شەربەت",
                smoothies: "سموزی",
                mojitos: "موجیتۆس",
                frappuccino: "فراپوچینۆ",
                milkshakes: "میڵک شەیک",
                softDrinks: "خواردنەوەی گازی",
                shishaItem: "ئینگلیزی",
                shishaLemoMint: "لیمۆ و نەعناع",
                shishaMintGum: "بنیشت و نەعناع",
                shishaMelonGum: "بنیشت و گندۆرە",
                shishaDoubleApple: "دوو سێو",
                shishaBaghdadi: "بەغدادی",
                shishaChampions: "چامپیۆنس",
                shishaChampions2: "چامپیۆنس ٢",
                shishaFreshFruit: "نێرگەلەی فرێش",
                reviews: "هەڵسەنگاندن",
                feedback: "هەڵسەنگاندن",
                menuItems: {
                    //salad 
                    cacik:"جاجک",
                    cacikDesc:"جاجک",

                    arugulasalad:"زەڵاتەی جەرجیر",
                    arugulasaladDesc:"زەڵاتەی جەرجیر",

                    burhanisalad:"زەڵاتەی بورهانی",
                    burhanisaladDesc:"burhani salad",

                    caesarsalad:"زەڵاتەی سیزەر",
                    caesarsaladDesc:"caesar",

                    coleslaw:"کۆڵسڵۆ",
                    coleslawDesc:"COLESLAW",

                    gavurdasalad:"زەڵاتەی گاویردا",
                    gavurdasaladDesc:"GAVURDA SALAD",

                    greecesalad:"زەڵاتەی یۆنانی",
                    greecesaladDesc:"GREECE SALAD",

                    grilledcheese:"پەنیری ‌حەلومی برژاو",
                    grilledcheeseDecs:"GRILLED HALLOUMI CHEESE",

                    // Open Buffet
                    mixedSalad: "زەڵاتەی تێکەڵاو",
                    mixedSaladDesc: "Perfectly grilled chicken",
                    
                    chicken: "ستەیکی مریشک",
                    chickenDesc: "مریشکی بە‌ڕاوی تەواو لەگەڵ گیاکان و بەهاراات",
                    meat: "ستەیکی گۆشت",
                    meatDesc: "گۆشتی نەرم و بە‌ڕاو لەگەڵ بەهاراتی نەریتی",
                    fish: "سەلەمۆن",
                    fishDesc: "ماسی تازەی بە‌ڕاو لەگەڵ لیمۆ و گیا",

                    MEATFAJITA:"فاهیتا گۆشت",
                    MEATFAJITADesc:"MEAT FAJITA",

                    chickenfajita:" فاهیتای مریشک ",
                    chickenfajitaDesc:"chicken fajita",

                    MEATISABELA:"گۆشتی ئیزابێلا ",
                    MEATISABELADesc:"MEAT ISABELA",

                    CHICKENCURRY:"مریشک کاری",
                    CHICKENCURRYDesc:"CHICKEN CURRY",

           
                    meatkofta: "کۆفتەی گۆشت",
                    meatkoftaDesc: "efvefve",
                    
                    shrimp: "ڕۆبیان",
                    shrimpDesc: "Crispy falafel balls with traditional Middle Eastern spices",

                    //SANDWICH
                    chickensandwich:"ساندویچی مریشک",
                    chickensandwichDecs:"CHICKEN SANDWICH",

                    meatsandwich:"ساندویچی گۆشت",
                    meatsandwichDesc:"MEAT SANDWICH",

                    meatburger:"بەرگەری گۆشت",
                    meatburgerDesc:"meatburger",
                    
                    
                      // Desserts
                    tiramisu: "کێکی تیرامیسۆ",
                    tiramisuDesc: "Traditional Italian dessert with coffee and mascarpone",

                    cheesecake: "چیس کێکی فستق",
                    cheesecakeDesc: "Rich and creamy cheesecake with berry compote",

                    cheesecakestrawberry: " چیس کێکی فراولە",
                    cheesecakestrawberryDesc: "biwbfiwbviwbvierbvir",

                    blackvalvet: " کێکی بلاک ڤالڤێت",
                    blackvalvetDesc:"ergferver",

                    dubaicake: "کێکی دوبەی",
                    dubaicakeDesc: "erfverf",

                    meites:" کێکی میتس",
                    meitesDesc:"ergerg",

                    redvalvet:"کێکی ڕێد ڤالڤێت",
                    redvalvetDesc: "wfrgerf",

                    sansebastian:" سان سباستیان",
                    sansebastianDesc:"wrfwrf",

                    croissant:"کرواسانی چامپیۆنس",
                    croissantDesc:"CROISSANT",

                    
                    croissantnormal:"کرواسانی سادە",
                    croissantnormalDesc:"rbrtgbrtg",

                    croissantcheese:"کرواسانی پەنیر",
                    croissantcheeseDesc:"erfererer",

                    croissantnutella:"کرواسانی نوتێلا",
                    croissantnutellaDesc:"erwergfwerg",

                    appletart:" تارتی سێو",
                    appletartDesc:"RFERFER",

                    minipancake:"پانکێکی بچوک",
                     minipancakeDesc:"MINI PANCAKE",

                     kebabwaffle:"وافڵی کەباب",
                    kebabwaffleDesc:"KEBAB WAFFLE",
                    
                    // Hot Drinks
                    singleEspresso: " ئێسپرێسۆی تاک",
                    singleEspressoDesc: "Rich Italian espresso shot",
                    doubleEspresso: " ئێسپرێسۆی دەبڵ",
                    doubleEspressoDesc: "Double shot of rich Italian espresso",
                    cappuccino: "کاپوچینۆ",
                    cappuccinoDesc: "Perfect blend of espresso and steamed milk",
                    espressoAvocado: "ESPRESSO AVOCADO",
                    espressoAvocadoDesc: "Unique blend of espresso with avocado",
                    hotLatte: "لاتیێ گەرم",
                    hotLatteDesc: "Smooth espresso with steamed milk",
                    americano: "ئەمریکانۆ",
                    americanoDesc: "Espresso with hot water",
                    lemonGreenTea: "چای سەوزی لیمۆ",
                    lemonGreenTeaDesc: "Refreshing green tea with fresh lemon",
                    darkChocolate: "شوکولاتەی تاڵ",
                    darkChocolateDesc: "Rich dark chocolate drink",

                    hotChocolate: "شوکولاتەی گەرم",
                    hotChocolateDesc: "Creamy hot chocolate",

                    italianHotChocolate: "قاوەی گەرمی ئیتالی",
                    italianHotChocolateDesc: "Thick Italian-style hot chocolate",

                    hotSpanishLatte: "سپانش لاتێ ",
                    hotSpanishLatteDesc: "Spanish-style latte with condensed milk",

                    vanillaLatte: "ڤانێلای لاتێ ",
                    vanillaLatteDesc: "Smooth latte with vanilla syrup",

                    mochaLatte: "مۆکا لاتێ",
                    mochaLatteDesc: "Coffee and chocolate blend",
                    turkishCoffee: "قاوەی تورکی",
                    turkishCoffeeDesc: "Traditional Turkish coffee",
                    qazwanCoffee: "قاوەی قەزوان",
                    qazwanCoffeeDesc: "Special Qazwan blend coffee",
                    filteredCoffee: "قاوەی فلتەرکراو",
                    filteredCoffeeDesc: "Classic filtered coffee",
                    
                    // Iced Coffee
                    icedCaramelLatte: "لاتیێ کەرەمیلی سارد",
                    icedCaramelLatteDesc: "Iced latte with caramel syrup",

                    icedLatte: "لاتیێ سارد",
                    icedLatteDesc: "Cold espresso with chilled milk",
                    icedSpanishLatte: "سپانیش لاتیێ سارد",
                    icedSpanishLatteDesc: "Iced Spanish-style latte",
                    icedMochaLatte: "مۆکا لاتیێ سارد",
                    icedMochaLatteDesc: "Iced coffee and chocolate blend",
                    icedPistachioLatte: "لاتیێ فستقی سارد",
                    icedPistachioLatteDesc: "Iced latte with pistachio flavor",
                    icedChocolate: "شکولاتەی تاڵی سارد",
                    icedChocolateDesc: "شکولاتەی سارد تاڵ",

                    icedDarkChocolate: "شکولاتەی سارد",
                    icedDarkChocolateDesc: "Cold dark chocolate drink",
                    icedAmericano: "ئەمریکانۆی سارد",
                    icedAmericanoDesc: "Espresso with cold water and ice",
                    icedCappuccino: "کاپوچینۆی سارد",
                    icedCappuccinoDesc: "Cold cappuccino with ice",
                    icedCoconut: "گوێزی هیندی سارد",
                    icedCoconutDesc: "Refreshing coconut drink",
                    
                    // Juices
                    championsJuice: "شەربەتی چامپیۆنس",
                    championsJuiceDesc: "Our signature fruit juice blend",
                    orangeJuice: "شەربەتی پرتەقاڵی فرێش",
                    orangeJuiceDesc: "Freshly squeezed orange juice",
                    lemonJuice: "شەربەتی لیمۆی فرێش",
                    lemonJuiceDesc: "Freshly squeezed lemon juice",
                    bananaMilk: "شەربەتی شیر و مۆز",
                    bananaMilkDesc: "Creamy banana milk shake",
                    appleBanana: "شەربەتی سێو و مۆز",
                    appleBananaDesc: "Apple and banana juice blend",

                    pomegranatejuice: "شەربەتی هەنار فرێش",
                    pomegranatejuiceDesc: "Apple and banana juice blend",
                    
                    // Smoothies
                    championsSmootbie: "سموزی چامپیۆنس",
                    championsSmootbieDesc: "Our signature smoothie blend",
                    mangoSmoothie: "سموزی مانگۆ",
                    mangoSmoothieDesc: "Fresh mango smoothie",

                    strawberrySmoothie: "STRAWBERRY SMOOTHIE",
                    strawberrySmoothieDesc: "Fresh strawberry smoothie",

                    orangeSmoothie: "سموزی پرتەقاڵ",
                    orangeSmoothieDesc: "Fresh orange smoothie",

                    pineappleStrawberry: "سموزی ئەنەناس",
                    pineappleStrawberryDesc: "Tropical pineapple and strawberry blend",

                    strawberrySmoothie: "سموزی فراولە",
                    strawberrySmoothieDesc: "Fresh strawberry smoothie",

                     strawberrybananaSmoothie: "سموزی مۆزو فراولە",
                    strawberrybananaSmoothieDesc: "Fresh strawberry smoothie",

                    lemonMint: "سموزی لیمۆ و نەعنەع",
                    lemonMintDesc: "Refreshing lemon mint smoothie",
                    
                    // Mojitos
                    strawberryMojito: " مۆهیتۆی فراولە ",
                    strawberryMojitoDesc: "Fresh strawberry mojito",

                    blueSky: "مۆهیتۆی بلو سکای ",
                    blueSkyDesc: "Blue sky flavored mojito",

                    blueberryMojito: "مۆهیتۆی بلوبێر",
                    blueberryMojitoDesc: "Fresh blueberry mojito",

                    classicmojito:"  مۆهیتۆی کلاسیک ",
                    classicmojitoDesc:"erger",

                    passionFruit: "مۆهیتۆی پیشن فروت ",
                    passionFruitDesc: "Passion fruit mojito",

                    mixedBerries: "مۆهیتۆی تووی تێکەڵاو ",
                    mixedBerriesDesc: "Mixed berries mojito",
                    blueHawai: "  مۆهیتۆی بلو هەوای ",
                    blueHawaiDesc: "Blue Hawaii tropical mojito",

                    coolLime: " مۆهیتۆی لیمۆی سارد ",
                    coolLimeDesc: "Cool lime mojito",
                    
                    // Frappuccino
                    championsFrappe: "فراپوچینۆ چامپیۆنس",
                    championsFrappeDesc: "Our signature frappuccino",

                    oreoFrappe: "فراپوچینۆ ئۆریۆ",
                    oreoFrappeDesc: "Oreo cookie frappuccino",

                    caramelFrappe: "فراپوچینۆ کەرەمیل",
                    caramelFrappeDesc: "Caramel frappuccino",

                    vanillaFrappe: "فراپوچینۆ ڤانێلا",
                    vanillaFrappeDesc: "Vanilla frappuccino",

                    chocolateFrappe: "فراپوچینۆ شکولاتە",
                    chocolateFrappeDesc: "Chocolate frappuccino",

                    lotusFrappe: "فراپوچینۆ لۆتۆس",
                    lotusFrappeDesc: "Lotus cookie frappuccino",
                    
                    // Milkshakes
                    nutellaMilkshake: "مێڵک شەیکی نوتێلا",
                    nutellaMilkshakeDesc: "Creamy Nutella milkshake",

                    vanillaMilkshake: "مێڵک شەیکی ڤانێلا",
                    vanillaMilkshakeDesc: "Classic vanilla milkshake",

                    kinderMilkshake: "مێڵک شەیکی کیندەر",
                    kinderMilkshakeDesc: "Kinder chocolate milkshake",

                    pistachioMilkshake: "مێڵک شەیکی فستق",
                    pistachioMilkshakeDesc: "Creamy pistachio milkshake",

                    lotusMilkshake: "مێڵک شەیکی لۆتۆس",
                    lotusMilkshakeDesc: "Lotus cookie milkshake",

                    strawberryMilkshake: " مێڵک شەیکی مۆز و فراولە ",
                    strawberryMilkshakeDesc: "Fresh strawberry milkshake",

                    strawberry: "مێڵک شەیکی فراولە ",
                    strawberryDesc: "Strawberry and banana milkshake",

                    bananaMilkshake: "مێڵک شەیکی مۆز",
                    bananaMilkshakeDesc: "Creamy banana milkshake",

                    oreo:"مێڵک شەیکی ئۆریو ",
                    oreodesc:"Eerferrtgrtgf",
                    
                    // Soft Drinks
                    championsRedbull: " ڕێدبولی چامپیۆنس",
                    championsRedbullDesc: "Our signature energy drink",
                    normalRedbull: "ڕێدبولی سادە",
                    normalRedbullDesc: "Classic Red Bull energy drink",
                    redbullMexican: "ڕێدبولی مەکسیکی",
                    redbullMexicanDesc: "Mexican style Red Bull",

                    cocaColaZero: "کۆکەکۆلای دایەت",
                    cocaColaZeroDesc: "Coca Cola Zero sugar",

                    sprite: "سپرایت",
                    spriteDesc: "Refreshing lemon-lime soda",

                    soda: "سۆدە",
                    sodaDesc: "Sparkling water",
                    water: "ئاو",
                    waterDesc: "Fresh bottled water"
                },
                allRightsReserved: "All rights reserved",
                followUs: "فۆلۆمان بکەن"
            },
            ar: {
 chooseLanguage: "اختر لغتك",
 selectLanguage: "اختر اللغة",
 callUs: "اتصل بنا",
 openHours: "ساعات العمل",
 visitUs: "زرنا",
 hours: "١١:٠٠ صباحاً - ١٢:٠٠ مساءً",
 address: " أربيل، كردستان العراق",
 Salad:"سلطة",
 openBuffet: "بوفيه مفتوح",
 dessert: "الحلويات",
 hotDrinks: "المشروبات الساخنة",
 icedCoffee: "القهوة المثلجة",
 juices: "العصائر",
 smoothies: "السموذي",
 mojitos: "الموهيتو",
 frappuccino: "الفرابوتشينو",
 milkshakes: " ميلك شيك ",
 softDrinks: "المشروبات الغازية",
 shishaItem: "إنجليزي",
 shishaLemoMint: "ليمون ونعناع",
 shishaMintGum: "علكة ونعناع",
 shishaMelonGum: "علكة وشمام",
 shishaDoubleApple: "تفاحتين",
 shishaBaghdadi: "بغدادي",
 shishaChampions: "شامبيونس",
 shishaChampions2: "شامبيونس ٢",
 shishaFreshFruit: "أركيلة طبيعية",
 reviews: "التقييمات",
 feedback: "التقييمات",
 menuItems: {
    //salad 
                    cacik:"جاجیک",
                    cacikDesc:"جاجیک",
                    
                    arugulasalad:"سلطة جرجير",
                    arugulasaladDesc:" جرجير",

                    burhanisalad:"سلطة برهاني",
                    burhanisaladDesc:"burhani salad",

                    caesarsalad:"سلطة سيزر",
                    caesarsaladDesc:"caesar",

                    coleslaw:"کولسلو",
                    coleslawDesc:"COLESLAW",

                    gavurdasalad:"سلطة كافوردا",
                    gavurdasaladDesc:"GAVURDA SALAD",

                    greecesalad:"سلطة يوناني",
                    greecesaladDesc:"GREECE SALAD",

                    grilledcheese:"جبن حلوم مشوي",
                    grilledcheeseDecs:"GRILLED HALLOUMI CHEESE",



   // Open Buffet
   
   mixedSalad: "سلطة مشكلة",
   mixedSaladDesc: "سلطة طازجة بخليط من الخضروات",
 
   chicken: " شريحة دجاج ",
   chickenDesc: "دجاج مشوي بتتبيلة أعشاب وبهارات",
 
   meat: "شريحة لحم",
   meatDesc: "قطع لحم طرية مشوية بتتبيلة تقليدية",
 
   fish: " سمك سلمون",
   fishDesc: "سمك طازج مشوي مع الليمون والاعشاب",

   MEATFAJITA:"فاهیتا لحم",
                    MEATFAJITADesc:"MEAT FAJITA",

                    chickenfajita:"فاهیتا دجاج",
                    chickenfajitaDesc:"chicken fajita",

                    MEATISABELA:"لحم ئیزابیلا ",
                    MEATISABELADesc:"MEAT ISABELA",

                    
                    CHICKENCURRY:"دجاج کاری",
                    CHICKENCURRYDesc:"CHICKEN CURRY",

   meatkofta:"كفتة لحم",
   meatkoftaDesc:"efvefve",
 
   shrimp: "روبيان",
   shrimpDesc: "روبيان مقرمش مع بهارات شرقية",

   //SANDWICH
                    chickensandwich:"سندويج دجاج",
                    chickensandwichDecs:"CHICKEN SANDWICH",

                    meatsandwich:"سندويج لحم",
                    meatsandwichDesc:"MEAT SANDWICH",

                    meatburger:"بركر لحم",
                    meatburgerDesc:"meatburger",
 
   // Desserts
   tiramisu: "كيكة تيراميسو",
   tiramisuDesc: "حلوى إيطالية تقليدية بالقهوة والماسكاربوني",
 
   cheesecake: "تشيز كيك بالفستق",
   cheesecakeDesc: "تشيز كيك كريمي غني مع صلصة التوت",
 
   cheesecakestrawberry: "تشيز كيك بالفراولة",
   cheesecakestrawberryDesc: "تشيز كيك بالفراولة الطازجة",
 
   blackvalvet: "كيكة بلاك فلفت",
   blackvalvetDesc: "كيك بلاك فيلفيت بطعم مميز",
 
   dubaicake: "كيكة دبي",
   dubaicakeDesc: "كيك بطابع شرقي مع لمسة عصرية",
 
   meites: "كيكة مايتس",
   meitesDesc: "كيكة خاصة بوصفة سرية",
 
   redvalvet: "كيكة ريد فلفت",
   redvalvetDesc: "كيك ريد فلفت غني بالكريمة",
 
   sansebastian: " سان سباستيان",
   sansebastianDesc: "تشيز كيك إسباني مخبوز",

                    croissant:"كرواسون شامبيونس",
                    croissantDesc:"CROISSANT",

                    croissantnormal:"كرواسون سادة ",
                    croissantnormalDesc:"rbrtgbrtg",

                    croissantcheese:"كرواسون بالجبن",
                    croissantcheeseDesc:"erfererer",

                    croissantnutella:"كرواسون نوتيلا",
                    croissantnutellaDesc:"erwergfwerg",

                    appletart:"تفاحة تارت ",
                    appletartDesc:"RFERFER",

                    minipancake:"ميني بانكيك",
                     minipancakeDesc:"MINI PANCAKE",

                     kebabwaffle:"کباب وافل",
                    kebabwaffleDesc:"KEBAB WAFFLE",
 
   // Hot Drinks
   singleEspresso: " سنغل إسبريسو ",
   singleEspressoDesc: "شوت إسبريسو إيطالي غني",
 
   doubleEspresso: " دبل إسبريسو ",
   doubleEspressoDesc: "شوت مضاعف من الإسبريسو الإيطالي",
 
   cappuccino: "كابتشينو",
   cappuccinoDesc: "مزيج مثالي من الإسبريسو والحليب المبخر",
 
   espressoAvocado: "إسبريسو أفوكادو",
   espressoAvocadoDesc: "خليط مميز من الإسبريسو والأفوكادو",
 
   hotLatte: "لاتيه ساخن",
   hotLatteDesc: "إسبريسو ناعم مع حليب مبخر",
 
   americano: "أمريكانو",
   americanoDesc: "إسبريسو مع ماء ساخن",
 
   lemonGreenTea: "شاي أخضر بالليمون",
   lemonGreenTeaDesc: "شاي أخضر منعش مع ليمون طازج",
 
   darkChocolate: "شوكولاتة داكنة",
   darkChocolateDesc: "مشروب غني بالشوكولاتة الداكنة",
 
   hotChocolate: "هوت شوكليت",
   hotChocolateDesc: "شوكولاتة ساخنة كريمية",
 
   italianHotChocolate: "شوكولاتة ساخنة إيطالية",
   italianHotChocolateDesc: "شوكولاتة ساخنة كثيفة على الطريقة الإيطالية",
 
   hotSpanishLatte: "سبانش لاتيه ",
   hotSpanishLatteDesc: "لاتيه إسباني بالحليب المكثف",
 
   vanillaLatte: "فانيلا لاتيه",
   vanillaLatteDesc: "لاتيه ناعم مع نكهة الفانيليا",
 
   mochaLatte: "موكا لاتيه",
   mochaLatteDesc: "مزيج القهوة والشوكولاتة",
 
   turkishCoffee: "قهوة تركية",
   turkishCoffeeDesc: "قهوة تركية تقليدية",
 
   qazwanCoffee: "قهوة قزوان",
   qazwanCoffeeDesc: "قهوة خاصة بمزيج قزوان",
 
   filteredCoffee: "قهوة مفلترة",
   filteredCoffeeDesc: "قهوة كلاسيكية مفلترة",
 
   // Iced Coffee
   icedCaramelLatte: "لاتيه بالكراميل باردة",
   icedCaramelLatteDesc: "لاتيه بارد مع نكهة الكراميل",
 
   icedLatte: "لاتيه باردة",
   icedLatteDesc: "إسبريسو بارد مع حليب مثلج",
 
   icedSpanishLatte: "سبانش لاتيه باردة",
   icedSpanishLatteDesc: "لاتيه بارد على الطريقة الإسبانية",
 
   icedMochaLatte: "موكا لاتيه باردة",
   icedMochaLatteDesc: "مزيج قهوة وشوكولاتة مثلجة",
 
   icedPistachioLatte: "لاتيه بالفستق باردة",
   icedPistachioLatteDesc: "لاتيه بارد بنكهات الفستق",
 
   icedChocolate: "شوكولاتة باردة",
   icedChocolateDesc: "مشروب شوكولاتة بارد",
 
   icedDarkChocolate: "شوكولاتة داكنة باردة",
   icedDarkChocolateDesc: "شوكولاتة داكنة مثلجة",
 
   icedAmericano: "أمريكانو باردة",
   icedAmericanoDesc: "إسبريسو مع ماء وثلج",
 
   icedCappuccino: "كابتشينو باردة",
   icedCappuccinoDesc: "كابتشينو بارد مع ثلج",
 
   icedCoconut: "جوز الهند باردة",
   icedCoconutDesc: "مشروب جوز الهند المنعش",
                    
                    // Juices
championsJuice: "عصير شامبيونس",
championsJuiceDesc: "مزيجنا المميز من العصائر الطبيعية",
orangeJuice: "عصير برتقال طازج",
orangeJuiceDesc: "عصير برتقال طازج معصور",

lemonJuice: "عصير ليمون طازج",
lemonJuiceDesc: "عصير ليمون طازج معصور",

bananaMilk: "عصير حليب وموز",
bananaMilkDesc: "مشروب حليب موز وكريمي",
appleBanana: "عصير تفاح وموز",
appleBananaDesc: "مزيج عصير التفاح والموز",

pomegranatejuice: "عصير رمان",
                    pomegranatejuiceDesc: "Apple and banana juice blend",

// Smoothies
championsSmootbie: " سموذي شامبيونس ",
championsSmootbieDesc: "مزيج السموذي المميز لدينا",
mangoSmoothie: "سموذي مانجو",
mangoSmoothieDesc: "سموذي مانجو طازج",
strawberrySmoothie: "سموذي فراولة",
strawberrySmoothieDesc: "سموذي فراولة طازج",
orangeSmoothie: "سموذي برتقال",
orangeSmoothieDesc: "سموذي برتقال طازج",
pineappleStrawberry: "سموذي أناناس وفراولة",
pineappleStrawberryDesc: "مزيج استوائي من الأناناس والفراولة",
lemonMint: "سموذي ليمون بالنعناع",
lemonMintDesc: "سموذي منعش بالليمون والنعناع",
 strawberrybananaSmoothie: "سموذي موزو فراولة ",
strawberrybananaSmoothieDesc: "Fresh strawberry smoothie",

// Mojitos
strawberryMojito: "موهيتو فراولة",
strawberryMojitoDesc: "موهيتو الفراولة الطازجة",

blueSky: "موهيتو بلو سكاي",
blueSkyDesc: "موهيتو بنكهة بلو سكاي",

blueberryMojito: "موهيتو توت أزرق",
blueberryMojitoDesc: "موهيتو بالتوت الأزرق الطازج",

classicmojito: "   موهيتو كلاسيك  ",
classicmojitoDesc: "الوصفة الكلاسيكية لموهيتو منعش",

passionFruit: "موهيتو باشن فروت",
passionFruitDesc: "موهيتو الباشن فروت ",

mixedBerries: "موهيتو توت مشكل",
mixedBerriesDesc: "موهيتو بمزيج من التوت",

blueHawai: "بلو هاواي",
blueHawaiDesc: "موهيتو استوائي على طريقة هاواي",

coolLime: "كووووووول لايم",
coolLimeDesc: "موهيتو منعش بالليمون",

// Frappuccino
championsFrappe: " فرابيه شامبيونس  ",
championsFrappeDesc: "الفرابيه المميز لدينا",
oreoFrappe: "فرابيه أوريو",
oreoFrappeDesc: "فرابيه ببسكويت الأوريو",
caramelFrappe: "فرابيه كراميل",
caramelFrappeDesc: "فرابيه بنكهة كراميل",
vanillaFrappe: "فرابيه فانيليا",
vanillaFrappeDesc: "فرابيه بنكهة الفانيليا",
chocolateFrappe: "فرابيه شوكولاتة",
chocolateFrappeDesc: "فرابيه بنكهة الشوكولاتة",
lotusFrappe: "فرابيه لوتس",
lotusFrappeDesc: "فرابيه ببسكويت اللوتس",



// Milkshakes
nutellaMilkshake: "ميلك شيك نوتيلا",
nutellaMilkshakeDesc: "ميلك شيك كريمي بنكهة النوتيلا",
vanillaMilkshake: "ميلك شيك فانيليا",
vanillaMilkshakeDesc: "ميلك شيك كلاسيكي بنكهة الفانيليا",
kinderMilkshake: "ميلك شيك كندر",
kinderMilkshakeDesc: "ميلك شيك بشوكولاتة كندر",
pistachioMilkshake: "ميلك شيك فستق",
pistachioMilkshakeDesc: "ميلك شيك كريمي بالفستق الحلبي",
lotusMilkshake: "ميلك شيك لوتس",
lotusMilkshakeDesc: "ميلك شيك ببسكويت لوتس",
strawberryMilkshake: " ميلك شيك فراولة وموز",
strawberryMilkshakeDesc: "ميلك شيك بالفراولة الطازجة",
strawberry: "ميلك شيك فراولة ",
strawberryDesc: "ميلك شيك بمزيج فراولة وموز",
bananaMilkshake: "ميلك شيك موز",
bananaMilkshakeDesc: "ميلك شيك كريمي بالموز",

oreo:" ميلك شيك اوریو ",
 oreodesc:"Eerfertrgrtgf",

// Soft Drinks
championsRedbull: "ريدبول شامبيونس",
championsRedbullDesc: "مشروب الطاقة ",
normalRedbull: "ريدبول عادي",
normalRedbullDesc: "ريدبول كلاسيكي",
redbullMexican: "ريدبول مكسيكي",
redbullMexicanDesc: "ريدبول على الطريقة المكسيكية",
cocaColaZero: "كوكاكولا زيرو",
cocaColaZeroDesc: "كوكاكولا بدون سكر",
sprite: "سبرايت",
spriteDesc: "مشروب غازي بنكهة الليمون",
soda: "صودة",
sodaDesc: "مياه غازية فوارة",
water: "ماء",
waterDesc: "مياه معدنية نقية"
},

allRightsReserved: "جميع الحقوق محفوظة",
followUs: "تابعنا"
            },
        };
    }

    static initialize() {
        try {
            appState.translations = this.getTranslationData();
            Logger.info('Translation data initialized successfully');
        } catch (error) {
            Logger.error('Failed to initialize translation data', error);
            throw new Error('Translation initialization failed');
        }
    }
}

/**
 * ============================================================================
 * LANGUAGE MANAGER
 * ============================================================================ 
 */

class LanguageManager {
    static getCurrentLanguage() {
        try {
            const path = window.location.pathname;
            const filename = path.split('/').pop() || 'index.html';
            
            if (filename.includes('ar.html') || path.includes('/ar/')) {
                return 'ar';
            }
            if (filename.includes('ku.html') || path.includes('/ku/')) {
                return 'ku';
            }
            return APP_CONFIG.DEFAULT_LANGUAGE;
        } catch (error) {
            Logger.error('Error determining current language', error);
            return APP_CONFIG.DEFAULT_LANGUAGE;
        }
    }

    static isLanguageSupported(languageCode) {
        return APP_CONFIG.SUPPORTED_LANGUAGES.includes(languageCode);
    }

    static navigateToLanguage(languageCode) {
        if (!this.isLanguageSupported(languageCode)) {
            Logger.error('Attempted navigation to unsupported language', languageCode);
            return;
        }

        Logger.info('Initiating navigation to language', languageCode);
        
        const currentPath = window.location.pathname;
        const currentDirectory = currentPath.substring(0, currentPath.lastIndexOf('/')) || '';
        
        const languageUrls = {
            'ku': `${currentDirectory}/ku.html`,
            'ar': `${currentDirectory}/ar.html`,
            'en': `${currentDirectory}/index.html`
        };
        
        const targetUrl = languageUrls[languageCode];
        
        if (!targetUrl) {
            Logger.error('No URL mapping found for language', languageCode);
            return;
        }

        try {
            this._showNavigationIndicator(languageCode);
            this._performNavigation(targetUrl, languageCode);
        } catch (error) {
            Logger.error('Navigation failed', error);
            this._performFallbackNavigation(languageCode);
        }
    }

    static _showNavigationIndicator(languageCode) {
        const loadingElement = UIManager.createLoadingIndicator(languageCode);
        document.body.appendChild(loadingElement);
        
        const languageScreen = document.querySelector(DOM_SELECTORS.LANGUAGE_SELECTION_SCREEN);
        if (languageScreen) {
            languageScreen.style.opacity = '0.3';
        }
    }

    static _performNavigation(targetUrl, languageCode) {
        setTimeout(() => {
            try {
                SessionManager.setLanguageSelection(languageCode);
                window.location.href = targetUrl;
            } catch (error) {
                Logger.error('Primary navigation method failed', error);
                this._performFallbackNavigation(languageCode);
            }
        }, APP_CONFIG.NAVIGATION_DELAY);
    }

    static _performFallbackNavigation(languageCode) {
        const fallbackUrls = {
            'ku': 'ku.html',
            'ar': 'ar.html',
            'en': 'index.html'
        };
        
        const fallbackUrl = fallbackUrls[languageCode];
        if (fallbackUrl) {
            window.location.href = fallbackUrl;
        }
    }
}

/**
 * ============================================================================
 * MENU DATA MANAGER
 * ============================================================================
 */

class MenuDataManager {
    static generateDefaultMenuData() {
        const currentLanguage = LanguageManager.getCurrentLanguage();
        const translations = appState.translations[currentLanguage] || 
                           appState.translations[APP_CONFIG.DEFAULT_LANGUAGE];
        
        return {
            //salad
            salad: {
            cacik: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/salad/cacik.jpg",
                    name: translations.menuItems.cacik || "CACIK",
                    description: translations.menuItems.cacikDesc || ""
                }, 

             mixedSalad: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/mixed-salad.jpg",
                    name: translations.menuItems.mixedSalad || "MIXED SALAD",
                    description: translations.menuItems.mixedSaladDesc || ""
                },

            arugulasalad: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/salad/arugula-salad.jpg",
                    name: translations.menuItems.arugulasalad || "ARUGULA SALAD",
                    description: translations.menuItems.arugulasaladDesc || ""
                },

                burhanisalad: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/salad/burhani-salad.jpg",
                    name: translations.menuItems.burhanisalad || "BURHANI SALAD",
                    description: translations.menuItems.burhanisaladDesc || ""
                },


                caesarsalad: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/salad/caesar-salad.jpg",
                    name: translations.menuItems.caesarsalad || "CAESAR SALAD",
                    description: translations.menuItems.caesarsaladDesc || ""
                },

                coleslaw: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/salad/coleslaw.jpg",
                    name: translations.menuItems.coleslaw || "COLESLAW",
                    description: translations.menuItems.coleslawDesc || ""
                },

                gavurdasalad: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/salad/gavurda-salad.jpg",
                    name: translations.menuItems.gavurdasalad || "GAVURDA SALAD",
                    description: translations.menuItems.gavurdasaladDesc || ""
                },

                greecesalad: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/salad/greece-salad.jpg",
                    name: translations.menuItems.greecesalad || "GREECE SALAD",
                    description: translations.menuItems.greecesaladDesc || ""
                },

                grilledcheese: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/salad/grilled-cheese.jpg",
                    name: translations.menuItems.grilledcheese || "GRILLED CHEESE",
                    description: translations.menuItems.grilledcheeseDecs || ""
                },
            },

            // Open Buffet
            openBuffet: {
             
                chicken: {
                    price: "10,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/chickenn.jpg",
                    name: translations.menuItems.chicken || "GRILLED CHICKEN",
                    description: translations.menuItems.chickenDesc || ""
                },
                meat: {
                    price: "12,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/meat.jpg",
                    name: translations.menuItems.meat || "GRILLED MEAT",
                    description: translations.menuItems.meatDesc || "Tender grilled meat with traditional seasoning"
                },
                MEATFAJITA: {
                    price: "12,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/meat-fajita.jpg",
                    name: translations.menuItems.MEATFAJITA || "MEAT FAJITA",
                    description: translations.menuItems.MEATFAJITADesc || "Tender grilled meat with traditional seasoning"
                },
                chickenfajita: {
                    price: "10,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/chicken-fajita.jpg",
                    name: translations.menuItems.chickenfajita || "CHICKEN FAJITA",
                    description: translations.menuItems.chickenfajitaDesc || "Tender grilled meat with traditional seasoning"
                },
                
                MEATISABELA: {
                    price: "12,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/meat-isabella.jpg",
                    name: translations.menuItems.MEATISABELA || "MEAT ISABELA",
                    description: translations.menuItems.MEATISABELADesc || "Tender grilled meat with traditional seasoning"
                },

                CHICKENCURRY: {
                    price: "10,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/chicken-curry.jpg",
                    name: translations.menuItems.CHICKENCURRY || "CHICKEN CURRY",
                    description: translations.menuItems.CHICKENCURRYDesc || "Tender grilled meat with traditional seasoning"
                },

                fish: {
                    price: "14,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/fish.jpg",
                    name: translations.menuItems.fish || "salmon",
                    description: translations.menuItems.fishDesc || "Fresh grilled fish with lemon and herbs"
                },

                 meatkofta: {
                    price: "10,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/meatkofta.jpg",
                    name: translations.menuItems.meatkofta || "meatkofta",
                    description: translations.menuItems.meatkoftaDesc || ""
                },
                shrimp: {
                    price: "14,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/open-buffet/shrimp.jpg",
                    name: translations.menuItems.shrimp || "SHRIMP",
                    description: translations.menuItems.shrimpDesc || "Crispy falafel balls with traditional Middle Eastern spices"
                }
            },

            sandwich: {
                chickensandwich: {
                    price: "8,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/sandwich/chicken-sandwich.jpg",
                    name: translations.menuItems.chickensandwich || "SANDWICH",
                    description: translations.menuItems.chickensandwichDecs || "Crispy falafel balls with traditional Middle Eastern spices"
                },

                meatsandwich: {
                    price: "10,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/sandwich/meat-sandwich.jpg",
                    name: translations.menuItems.meatsandwich || "SANDWICH",
                    description: translations.menuItems.meatsandwichDesc || "Crispy falafel balls with traditional Middle Eastern spices"
                },

                meatburger: {
                    price: "10,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/sandwich/meat-burger.jpg",
                    name: translations.menuItems.meatburger || "burger",
                    description: translations.menuItems.meatburgerDesc || "Crispy falafel balls with traditional Middle Eastern spices"
                },


            },
            
            // Desserts
            desserts: {
                tiramisu: {
                    price: "5,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/tiramisu.jpg",
                    name: translations.menuItems.tiramisu || "",
                    description: translations.menuItems.tiramisuDesc || ""
                },
                cheesecakepistachio: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/cheese-cake-pistachio.jpg",
                    name: translations.menuItems.cheesecake || "",
                    description: translations.menuItems.cheesecakeDesc || ""
                },
                  cheesecakestrawberry: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/cheese-cake-strawberry.jpg",
                    name: translations.menuItems.cheesecakestrawberry || "",
                    description: translations.menuItems.cheesecakestrawberryDesc || ""
                },
                  blackvalvet: {
                    price: "5,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/black-valvet.jpg",
                    name: translations.menuItems.blackvalvet || "",
                    description: translations.menuItems.blackvalvetDesc || ""
                },
                dubaicake: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/dubai-cake.jpg",
                    name: translations.menuItems.dubaicake || "",
                    description: translations.menuItems.dubaicakeDesc || ""
                },
                meites: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/meites.jpg",
                    name: translations.menuItems.meites || "",
                    description: translations.menuItems.meitesDesc || ""
                },
                redvalvet: {
                    price: "5,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/red-valvet.jpg",
                    name: translations.menuItems.redvalvet || "",
                    description: translations.menuItems.redvalvetDesc || ""
                },
                sansebastian: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/san-sebastian.jpg",
                    name: translations.menuItems.sansebastian || "",
                    description: translations.menuItems.sansebastianDesc || ""
                },

                

                appletart: {
                    price: "6,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/appletart.jpg",
                    name: translations.menuItems.appletart || "",
                    description: translations.menuItems.appletartDesc || ""
                },     

                minipancake: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/mini-pancake.jpg",
                    name: translations.menuItems.minipancake || "",
                    description: translations.menuItems.minipancakeDesc || ""
                },

                kebabwaffle: {
                    price: "3,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/kebab-waffle.jpg",
                    name: translations.menuItems.kebabwaffle || "",
                    description: translations.menuItems.kebabwaffleDesc || ""
                },
            },

            croissant: {
                croissant: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/croissant.jpg",
                    name: translations.menuItems.croissant || "",
                    description: translations.menuItems.croissantDesc || ""
                },

                 croissantnormal: {
                    price: "3,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/croissantnormal.jpg",
                    name: translations.menuItems.croissantnormal || "",
                    description: translations.menuItems.croissantnormalDesc || ""
                },

                  croissantcheese: {
                    price: "3,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/croissantcheese.jpg",
                    name: translations.menuItems.croissantcheese || "",
                    description: translations.menuItems.croissantcheeseDesc || ""
                },

                 croissantnutella: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/dessert/croissantnutella.jpg",
                    name: translations.menuItems.croissantnutella || "",
                    description: translations.menuItems.croissantnutellaDesc || ""
                },
            },
            
            // Hot Drinks
            hotDrinks: {
                singleEspresso: {
                    price: "3,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/single-espresso.jpg",
                    name: translations.menuItems.singleEspresso || "SINGLE ESPRESSO",
                    description: translations.menuItems.singleEspressoDesc || ""
                },
                doubleEspresso: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/double-espresso.jpg",
                    name: translations.menuItems.doubleEspresso || "DOUBLE ESPRESSO",
                    description: translations.menuItems.doubleEspressoDesc || "Double shot of rich Italian espresso"
                },
                cappuccino: {
                    price: "4,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/cappuccino.jpg",
                     name: translations.menuItems.cappuccino || "CAPPUCCINO",
                    description: translations.menuItems.cappuccinoDesc || ""
                },
                hotLatte: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/hot-latte.jpg",
                    name: translations.menuItems.hotLatte || "HOT LATTE",
                    description: translations.menuItems.hotLatteDesc || ""
                },
                americano: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/americano.jpg",
                    name: translations.menuItems.americano || "AMERICANO",
                    description: translations.menuItems.americanoDesc || "Espresso with hot water"
                },
                turkishCoffee: {
                    price: "3,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/turkish-coffee.jpg",
                    name: translations.menuItems.turkishCoffee || "TURKISH COFFEE",
                    description: translations.menuItems.turkishCoffeeDesc || "Traditional Turkish coffee"
                },
                qazwanCoffee: {
                    price: "3,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/qazwan-coffee.jpg",
                    name: translations.menuItems.qazwanCoffee || "QAZWAN COFFEE",
                    description: translations.menuItems.qazwanCoffeeDesc || "Special Qazwan blend coffee"
                },
                filteredCoffee: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/filtered-coffee.jpg",
                    name: translations.menuItems.filteredCoffee || "FILTERED COFFEE",
                    description: translations.menuItems.filteredCoffeeDesc || "Classic filtered coffee"
                },
                darkChocolate: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/dark-chocolate.jpg",
                    name: translations.menuItems.darkChocolate || "",
                    description: translations.menuItems.darkChocolateDesc || ""
                },
                hotChocolate: {
                    price: "4,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/hot-chocolate.jpg",
                    name: translations.menuItems.hotChocolate || "FILTERED COFFEE",
                    description: translations.menuItems.hotChocolateDesc || "Classic filtered coffee"
                },
                hotSpanishLatte: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/Hot-spanish-latte.jpg",
                    name: translations.menuItems.hotSpanishLatte || "FILTERED COFFEE",
                    description: translations.menuItems.hotSpanishLatteDesc || "Classic filtered coffee"
                },
                vanillaLatte: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/vanilla-latte.jpg",
                    name: translations.menuItems.vanillaLatte || "VANILLA LATTE",
                    description: translations.menuItems.vanillaLatteDesc || "Classic filtered coffee"
                },
                mochaLatte: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/mocha-latte.jpg",
                    name: translations.menuItems.mochaLatte || "VANILLA LATTE",
                    description: translations.menuItems.mochaLatteDesc || "Classic filtered coffee"
                },
                 lemonGreenTea: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/hot-drinks/lemon-green-tea.jpg",
                    name: translations.menuItems.lemonGreenTea || "VANILLA LATTE",
                    description: translations.menuItems.lemonGreenTeaDesc || "Classic filtered coffee"
                },

            },
            
            // Iced Coffee
            icedCoffee: {
                icedAmericano: {
                    price: "4,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-americano.jpg",
                    name: translations.menuItems.icedAmericano || "",
                    description: translations.menuItems.icedAmericanoDesc || ""
                },
                icedCappuccino: {
                    price: "4,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-cappuccino.jpg",
                    name: translations.menuItems.icedCappuccino || "",
                    description: translations.menuItems.icedCappuccinoDesc || ""
                },
                icedCaramelLatte: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-caramel-latte.JPG",
                    name: translations.menuItems.icedCaramelLatte || "",
                    description: translations.menuItems.icedCaramelLatteDesc || ""
                },
                icedChocolate: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-chocolate.jpg",
                    name: translations.menuItems.icedChocolate || "",
                    description: translations.menuItems.icedChocolateDesc || ""
                },
                icedCoconut: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-coconut.jpg",
                    name: translations.menuItems.icedCoconut || "",
                    description: translations.menuItems.icedCoconutDesc || ""
                },
                icedDarkChocolate: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-dark-chocolate.jpg",
                    name: translations.menuItems.icedDarkChocolate || "",
                    description: translations.menuItems.icedDarkChocolateDesc || ""
                },
                icedLatte: {
                    price: "4,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-latte.jpg",
                    name: translations.menuItems.icedLatte || "",
                    description: translations.menuItems.icedLatteDesc || ""
                },
                icedMochaLatte: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-mocha-latte.jpg",
                    name: translations.menuItems.icedMochaLatte || "",
                    description: translations.menuItems.icedMochaLatteDesc || ""
                },
                icedPistachioLatte: {
                    price: "6,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-pistachio-latte.jpg",
                    name: translations.menuItems.icedPistachioLatte || "",
                    description: translations.menuItems.icedPistachioLatteDesc || ""
                },
                icedSpanishLatte: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/Iced-coffee/iced-spanish-latte.jpg",
                    name: translations.menuItems.icedSpanishLatte || "",
                    description: translations.menuItems.icedSpanishLatteDesc || ""
                },
            },
            
            // Juices
            juices: {
                championsJuice: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/juices/champions-juice.jpg",
                    name: translations.menuItems.championsJuice || "",
                    description: translations.menuItems.championsJuiceDesc || ""
                },
                appleBanana: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/juices/apple-banana.jpg",
                    name: translations.menuItems.appleBanana || "",
                    description: translations.menuItems.appleBananaDesc || ""
                },
                bananaMilk: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/juices/banana-milk.jpg",
                    name: translations.menuItems.bananaMilk || "bananaMilk",
                    description: translations.menuItems.bananaMilkDesc || ""
                },
                lemonJuice: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/juices/lemon-juice.jpg",
                    name: translations.menuItems.lemonJuice || "",
                    description: translations.menuItems.lemonJuiceDesc || ""
                },
                orangeJuice: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/juices/orange-juice.jpg",
                    name: translations.menuItems.orangeJuice || "",
                    description: translations.menuItems.orangeJuiceDesc || ""
                },

                pomegranatejuice: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/juices/pomegranate-juice.jpg",
                    name: translations.menuItems.pomegranatejuice || "",
                    description: translations.menuItems.pomegranatejuiceDesc || ""
                },
            },
            
            // Smoothies
            smoothies: {
                championsSmootbie: {
                    price: "7,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/smoothie/champions-smoothie.jpg",
                    name: translations.menuItems.championsSmootbie || "",
                    description: translations.menuItems.championsSmootbieDesc || ""
                },
                mangoSmoothie: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/smoothie/orange-smoothie.jpg",
                    name: translations.menuItems.mangoSmoothie || "",
                    description: translations.menuItems.mangoSmoothieDesc || ""
                },

                lemonMint: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/smoothie/lemon-mint.jpg",
                    name: translations.menuItems.lemonMint || "",
                    description: translations.menuItems.lemonMintDesc || ""
                },
                orangeSmoothie: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/smoothie/orange-smoothie.jpg",
                    name: translations.menuItems.orangeSmoothie || "",
                    description: translations.menuItems.orangeSmoothieDesc || ""
                },
                pineappleStrawberry: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/smoothie/champions-smoothie.jpg",
                    name: translations.menuItems.pineappleStrawberry || "",
                    description: translations.menuItems.pineappleStrawberryDesc || ""
                },
                strawberrySmoothie: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/smoothie/strawberry-smoothie.jpg",
                    name: translations.menuItems.strawberrySmoothie || "",
                    description: translations.menuItems.strawberrySmoothieDesc || ""
                },

                 strawberrybananaSmoothie: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/smoothie/strawberry-smoothie.jpg",
                    name: translations.menuItems.strawberrybananaSmoothie || "",
                    description: translations.menuItems.strawberrybananaSmoothieDesc || ""
                },
            },
            
            // Mojitos
            mojitos: {
                blueSky: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/mojito/blue-sky.jpg",
                    name: translations.menuItems.blueSky || "",
                    description: translations.menuItems.blueSkyDesc || ""
                },
                blueberryMojito: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/mojito/blueberry-mojito.jpg",
                    name: translations.menuItems.blueberryMojito || "",
                    description: translations.menuItems.blueberryMojitoDesc || ""
                },
                classicmojito: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/mojito/classic-mojito.jpg",
                    name: translations.menuItems.classicmojito || "",
                    description: translations.menuItems.classicmojitoDesc || ""
                },
                passionFruit: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/mojito/passion-fruit.jpg",
                    name: translations.menuItems.passionFruit || "",
                    description: translations.menuItems.passionFruitDesc || ""
                },
                strawberryMojito: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/mojito/mixed-berry.jpg",
                    name: translations.menuItems.strawberryMojito || "",
                    description: translations.menuItems.strawberryMojitoDesc || ""
                },
                 mixedBerries: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/mojito/mixed-berry.jpg",
                    name: translations.menuItems.mixedBerries || "",
                    description: translations.menuItems.mixedBerriesDesc || ""
                },
            },
            
            // Frappuccino
            frappuccino: {
                championsFrappe: {
                    price: "7,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/frappuccino/champions-frappe.jpg",
                    name: translations.menuItems.championsFrappe || "",
                    description: translations.menuItems.championsFrappeDesc || ""
                },
                caramelFrappe: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/frappuccino/caramel-frappe.jpg",
                    name: translations.menuItems.caramelFrappe || "",
                    description: translations.menuItems.caramelFrappeDesc || ""
                },
                chocolateFrappe: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/frappuccino/chocolate-frappe.jpg",
                    name: translations.menuItems.chocolateFrappe || "",
                    description: translations.menuItems.chocolateFrappeDesc || ""
                },
                lotusFrappe: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/frappuccino/lotus-frappe.jpg",
                    name: translations.menuItems.lotusFrappe || "",
                    description: translations.menuItems.lotusFrappeDesc || ""
                },
                oreoFrappe: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/frappuccino/oreo-frappe.jpg",
                    name: translations.menuItems.oreoFrappe || "",
                    description: translations.menuItems.lotusFrappeDesc || ""
                },
                oreoFrappe: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/frappuccino/oreo.jpg",
                    name: translations.menuItems.oreoFrappe || "",
                    description: translations.menuItems.oreoFrappeDesc || ""
                },
                vanillaFrappe: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/frappuccino/vanilla-frappe.jpg",
                    name: translations.menuItems.vanillaFrappe || "LOTUS FRAPPE",
                    description: translations.menuItems.vanillaFrappeDesc || "Lotus cookie frappuccino"
                },
            },
            
            // Milkshakes
            milkshakes: {
                bananaMilkshake: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/milkshake/Banana.jpg",
                    name: translations.menuItems.bananaMilkshake || "",
                    description: translations.menuItems.bananaMilkshakeDesc || ""
                },
                kinderMilkshake: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/milkshake/kinder.jpg",
                    name: translations.menuItems.kinderMilkshake || "",
                    description: translations.menuItems.kinderMilkshakeDesc || ""
                },
                lotusMilkshake: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/milkshake/lotus.jpg",
                    name: translations.menuItems.lotusMilkshake || "",
                    description: translations.menuItems.lotusMilkshakeDesc || ""
                },
                nutellaMilkshake: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/milkshake/nutella-milkshake.jpg",
                    name: translations.menuItems.nutellaMilkshake || "",
                    description: translations.menuItems.nutellaMilkshakeDesc || ""
                },
                pistachioMilkshake: {
                    price: "6,500",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/milkshake/pistachio.jpg",
                    name: translations.menuItems.pistachioMilkshake || "",
                    description: translations.menuItems.pistachioMilkshakeDesc || ""
                },
                strawberryMilkshake: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/milkshake/strawberry-banana.jpg",
                    name: translations.menuItems.strawberryMilkshake || "",
                    description: translations.menuItems.strawberryMilkshakeDesc || ""
                },

                 strawberry: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/milkshake/strawberry.jpg",
                    name: translations.menuItems.strawberry || "",
                    description: translations.menuItems.strawberryDesc || ""
                },
                vanillaMilkshake: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/milkshake/vanilla.jpg",
                    name: translations.menuItems.vanillaMilkshake || "",
                    description: translations.menuItems.vanillaMilkshakeDesc || ""
                },

                 oreo: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/frappuccino/oreo.jpg",
                    name: translations.menuItems.oreo || "",
                    description: translations.menuItems.oreodesc || ""
                },
            },
            
            // Soft Drinks
            softDrinks: {
                championsRedbull: {
                    price: "6,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/soft-drinks/champions-redbull.jpg",
                    name: translations.menuItems.championsRedbull || "",
                    description: translations.menuItems.championsRedbullDesc || ""
                },
                normalRedbull: {
                    price: "4,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/soft-drinks/normal-redbull.jpg",
                    name: translations.menuItems.normalRedbull || "",
                    description: translations.menuItems.normalRedbullDesc || ""
                },
                redbullMexican: {
                    price: "5,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/soft-drinks/mexican-redbull.jpg",
                    name: translations.menuItems.redbullMexican || "",
                    description: translations.menuItems.redbullMexicanDesc || ""
                },
                cocaColaZero: {
                    price: "1,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/soft-drinks/cocacola-zero.jpg",
                    name: translations.menuItems.cocaColaZero || "",
                    description: translations.menuItems.cocaColaZeroDesc || ""
                },
                soda: {
                    price: "1,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/soft-drinks/soda.jpg",
                    name: translations.menuItems.soda || "",
                    description: translations.menuItems.sodaDesc || ""
                },
                water: {
                    price: "1,000",
                    image: "/20259597/menu/champions_saladbar&cafe/img-champions/soft-drinks/water.jpg",
                    name: translations.menuItems.water || "",
                    description: translations.menuItems.waterDesc || ""
                }
            },

            // Shisha
            shisha: {
                english: {
                    price: "12,000",
                    image: "img-champions/shisha/shisha-table.png",
                    name: translations.shishaItem || "English",
                    description: ""
                },
                lemoMint: {
                    price: "12,000",
                    image: "img-champions/shisha/shisha-table.png",
                    name: translations.shishaLemoMint || "Lemo Mint",
                    description: ""
                },
                mintGum: {
                    price: "12,000",
                    image: "img-champions/shisha/shisha-table.png",
                    name: translations.shishaMintGum || "Mint Gum",
                    description: ""
                },
                melonGum: {
                    price: "12,000",
                    image: "img-champions/shisha/shisha-table.png",
                    name: translations.shishaMelonGum || "Melon Gum",
                    description: ""
                },
                doubleApple: {
                    price: "12,000",
                    image: "img-champions/shisha/shisha-table.png",
                    name: translations.shishaDoubleApple || "Double Apple",
                    description: ""
                },
                baghdadi: {
                    price: "12,000",
                    image: "img-champions/shisha/shisha-table.png",
                    name: translations.shishaBaghdadi || "Baghdadi",
                    description: ""
                },
                champions: {
                    price: "15,000",
                    image: "img-champions/shisha/champions-vip.png",
                    name: translations.shishaChampions || "Champions",
                    description: ""
                },
                champions2: {
                    price: "15,000",
                    image: "img-champions/shisha/champions-vip.png",
                    name: translations.shishaChampions2 || "Champions 2",
                    description: ""
                },
                freshFruit: {
                    price: "18,000",
                    image: "img-champions/shisha/fresh-fruit-vvip.png",
                    name: translations.shishaFreshFruit || "Fresh Fruit",
                    description: ""
                }
            }
        };
    }

    static initialize() {
        try {
            appState.menuData = this.generateDefaultMenuData();
            Logger.info('Complete menu data initialized successfully');
        } catch (error) {
            Logger.error('Failed to initialize menu data', error);
            throw new Error('Menu data initialization failed');
        }
    }
}

/**
 * ============================================================================
 * SESSION MANAGER
 * ============================================================================
 */

class SessionManager {
    static setLanguageSelection(languageCode) {
        try {
            sessionStorage.setItem(STORAGE_KEYS.LANGUAGE_SELECTED, 'true');
            sessionStorage.setItem(STORAGE_KEYS.SELECTED_LANGUAGE, languageCode);
            appState.isLanguageSelectedInSession = true;
            Logger.info('Language selection stored in session', languageCode);
        } catch (error) {
            Logger.error('Failed to store language selection', error);
        }
    }

    static hasLanguageSelection() {
        try {
            return sessionStorage.getItem(STORAGE_KEYS.LANGUAGE_SELECTED) === 'true';
        } catch (error) {
            Logger.error('Failed to check language selection status', error);
            return false;
        }
    }

    static getSelectedLanguage() {
        try {
            return sessionStorage.getItem(STORAGE_KEYS.SELECTED_LANGUAGE);
        } catch (error) {
            Logger.error('Failed to retrieve selected language', error);
            return null;
        }
    }
}

/**
 * ============================================================================
 * FEEDBACK MANAGER 
 * ============================================================================
 */

class FeedbackManager {
    static initialize() {
        try {
            const reviewsButton = document.querySelector(DOM_SELECTORS.REVIEWS_BUTTON);
            
            if (reviewsButton) {
                this._setupFeedbackButton(reviewsButton);
                Logger.info('Feedback button initialized successfully');
            } else {
                Logger.warning('Feedback button not found in DOM');
            }
        } catch (error) {
            Logger.error('Failed to initialize feedback manager', error);
        }
    }

    static _setupFeedbackButton(feedbackButton) {
        feedbackButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            
            this._openFeedbackPage();
            
            if (appState.isMobileMenuOpen) {
                MobileMenuManager.closeMobileMenu();
            }
        });

        feedbackButton.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this._openFeedbackPage();
                
                if (appState.isMobileMenuOpen) {
                    MobileMenuManager.closeMobileMenu();
                }
            }
        });
    }

    static _openFeedbackPage() {
        try {
            const currentLanguage = LanguageManager.getCurrentLanguage();
            const feedbackUrl = this._getFeedbackUrl(currentLanguage);
            
            Logger.info('Opening feedback page', feedbackUrl);
            window.location.href = feedbackUrl;
            
        } catch (error) {
            Logger.error('Failed to open feedback page', error);
            window.location.href = APP_CONFIG.FEEDBACK_PAGES.en;
        }
    }

    static _getFeedbackUrl(languageCode) {
        const feedbackPage = APP_CONFIG.FEEDBACK_PAGES[languageCode] || APP_CONFIG.FEEDBACK_PAGES.en;
        const currentPath = window.location.pathname;
        const currentDirectory = currentPath.substring(0, currentPath.lastIndexOf('/')) || '';
        return `${currentDirectory}/${feedbackPage}`;
    }
}

/**
 * ============================================================================
 * UI MANAGER - SIMPLIFIED (NO ORIENTATION WARNINGS)
 * ============================================================================
 */

class UIManager {
    static createLoadingIndicator(languageCode) {
        const loadingMessages = {
            'ku': {
                primary: 'زمان کوردی دەباگیریت...',
                secondary: 'Loading Kurdish...',
                rtl: true
            },
            'ar': {
                primary: 'جاری تحميل العربية...',
                secondary: 'Loading Arabic...',
                rtl: true
            },
            'en': {
                primary: 'Loading English...',
                secondary: ''
            }
        };

        const config = loadingMessages[languageCode] || loadingMessages.en;
        const loadingElement = document.createElement('div');
        
        loadingElement.style.cssText = `
            position: fixed; inset: 0;
            background: linear-gradient(135deg, #0e3f21 0%, #0c3a1e 100%);
            color: white; z-index: 10001;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; text-align: center;
            ${config.rtl ? 'direction: rtl;' : ''}
        `;

        loadingElement.innerHTML = `
            <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                <div style="width: 220px; max-width: 70vw; border-radius: 8px; overflow: hidden; animation: spin 1s linear infinite;">
                    <img src="img-champions/champions-cafe-shisha-logo.png" alt="Loading" 
                         style="display: block; width: 100%; height: auto; object-fit: contain;">
                </div>
            </div>
        `;

        this._addLoadingAnimationStyles();
        return loadingElement;
    }

    static _addLoadingAnimationStyles() {
        if (!document.getElementById('loading-animations')) {
            const style = document.createElement('style');
            style.id = 'loading-animations';
            style.textContent = `
                @keyframes spin { 
                    from { transform: rotate(0deg); } 
                    to { transform: rotate(360deg); } 
                }
                @keyframes progress { 
                    from { width: 0%; } 
                    to { width: 100%; } 
                }
            `;
            document.head.appendChild(style);
        }
    }

    static updateLanguageInterface(languageCode, languageText, isPrompt = false) {
        const elements = {
            selectedLanguageText: document.getElementById('selectedLanguageText'),
            navbarSelectedLanguage: document.getElementById('navbarSelectedLanguage'),
            mobileSelectedLanguage: document.getElementById('mobileSelectedLanguage')
        };

        const shortCodes = {
            'en': isPrompt ? 'SELECT' : 'EN',
            'ku': isPrompt ? 'هەڵبژێرە' : 'کو',
            'ar': isPrompt ? 'اختر' : 'ع'
        };

        try {
            if (elements.selectedLanguageText) {
                elements.selectedLanguageText.textContent = languageText;
            }
            if (elements.mobileSelectedLanguage) {
                elements.mobileSelectedLanguage.textContent = languageText;
            }
            if (elements.navbarSelectedLanguage) {
                elements.navbarSelectedLanguage.textContent = shortCodes[languageCode] || 'SELECT';
            }

            // Only update language option selection if not showing prompt
            if (!isPrompt) {
                this._updateLanguageOptionSelection(languageCode);
            }
            
            Logger.info('Language interface updated successfully', languageCode);
        } catch (error) {
            Logger.error('Failed to update language interface', error);
        }
    }

    static _updateLanguageOptionSelection(languageCode) {
        const languageOptions = document.querySelectorAll(DOM_SELECTORS.LANGUAGE_OPTIONS);
        languageOptions.forEach(option => {
            option.classList.remove(CSS_CLASSES.SELECTED);
            if (option.dataset.lang === languageCode) {
                option.classList.add(CSS_CLASSES.SELECTED);
            }
        });
    }

    static clearLanguageOptionSelections() {
        try {
            const languageOptions = document.querySelectorAll(DOM_SELECTORS.LANGUAGE_OPTIONS);
            languageOptions.forEach(option => {
                option.classList.remove(CSS_CLASSES.SELECTED);
            });
            Logger.info('All language option selections cleared');
        } catch (error) {
            Logger.error('Failed to clear language option selections', error);
        }
    }

    static markLanguageAsSelected(languageCode) {
        try {
            // First clear all selections
            this.clearLanguageOptionSelections();
            
            // Then mark the specified language as selected
            const languageOptions = document.querySelectorAll(DOM_SELECTORS.LANGUAGE_OPTIONS);
            languageOptions.forEach(option => {
                if (option.dataset.lang === languageCode) {
                    option.classList.add(CSS_CLASSES.SELECTED);
                }
            });
            Logger.info('Language marked as selected', languageCode);
        } catch (error) {
            Logger.error('Failed to mark language as selected', error);
        }
    }

    /**
     * ============================================================================
     * NEW: UPDATE NAVIGATION LANGUAGE BUTTONS ACTIVE STATE
     * ============================================================================
     */
    static updateNavigationLanguageButtons(languageCode) {
        try {
            // Update desktop language buttons
            const desktopButtons = document.querySelectorAll(DOM_SELECTORS.DESKTOP_LANG_BUTTONS);
            desktopButtons.forEach(button => {
                button.classList.remove(CSS_CLASSES.ACTIVE);
                if (button.dataset.lang === languageCode) {
                    button.classList.add(CSS_CLASSES.ACTIVE);
                }
            });

            // Update mobile language buttons
            const mobileButtons = document.querySelectorAll(DOM_SELECTORS.MOBILE_LANG_BUTTONS);
            mobileButtons.forEach(button => {
                button.classList.remove(CSS_CLASSES.ACTIVE);
                if (button.dataset.lang === languageCode) {
                    button.classList.add(CSS_CLASSES.ACTIVE);
                }
            });

            Logger.info('Navigation language buttons updated for language:', languageCode);
        } catch (error) {
            Logger.error('Failed to update navigation language buttons', error);
        }
    }
}

/**
 * ============================================================================
 * LANGUAGE SELECTION MANAGER - FIXED FOR PROPER WEBSITE DISPLAY
 * ============================================================================
 */

class LanguageSelectionManager {
    static showMandatoryLanguageSelection() {
        try {
            const languageSelectionScreen = document.querySelector(DOM_SELECTORS.LANGUAGE_SELECTION_SCREEN);
            
            if (!languageSelectionScreen) {
                throw new Error('Language selection screen element not found');
            }

            // Show the language selection screen
            languageSelectionScreen.classList.remove(CSS_CLASSES.HIDDEN);
            languageSelectionScreen.setAttribute('data-mandatory', 'true');
            
            // Hide main content initially
            this._hideMainContent();
            this._lockBodyScroll();
            this._setupLanguageSelectionEvents();
            
            document.addEventListener('keydown', this._preventEscapeClose);
            
            Logger.info('Mandatory language selection screen displayed');
        } catch (error) {
            Logger.error('Failed to show language selection screen', error);
        }
    }

    static processLanguageSelection(languageCode, languageText) {
        if (!LanguageManager.isLanguageSupported(languageCode)) {
            Logger.error('Invalid language code provided', languageCode);
            return;
        }

        try {
            appState.isLanguageSelectedInSession = true;
            
            // Update interface to show the selected language name and mark it as selected
            UIManager.updateLanguageInterface(languageCode, languageText, false);
            UIManager.markLanguageAsSelected(languageCode); // Keep the selected language highlighted
            UIManager.updateNavigationLanguageButtons(languageCode); // Update navigation buttons
            LanguagePopupManager.closeLanguagePopup();
            
            const currentLanguage = LanguageManager.getCurrentLanguage();
            Logger.info('Language selection processed', { current: currentLanguage, selected: languageCode });
            
            if (languageCode !== currentLanguage) {
                LanguageManager.navigateToLanguage(languageCode);
            } else {
                this.proceedToWebsite();
            }
        } catch (error) {
            Logger.error('Error processing language selection', error);
        }
    }

    static proceedToWebsite() {
        Logger.info('Proceeding to website content');
        
        try {
            const languageSelectionScreen = document.querySelector(DOM_SELECTORS.LANGUAGE_SELECTION_SCREEN);
            if (languageSelectionScreen) {
                // Hide language selection screen with animation
                languageSelectionScreen.style.transition = 'opacity 0.5s ease, visibility 0.5s ease';
                languageSelectionScreen.style.opacity = '0';
                languageSelectionScreen.style.visibility = 'hidden';
                
                setTimeout(() => {
                    languageSelectionScreen.classList.add(CSS_CLASSES.HIDDEN);
                }, 500);
                
                this._restoreBodyScroll();
                document.removeEventListener('keydown', this._preventEscapeClose);
            }
            
            // Show website content
            this._showWebsiteContent();
            
            Logger.info('Language selection completed - website content accessible');
        } catch (error) {
            Logger.error('Error proceeding to website', error);
        }
    }

    static setCurrentLanguageSelection() {
        try {
            // Show "Select Language" text initially and don't mark any language as selected
            const currentLanguage = LanguageManager.getCurrentLanguage();
            const languageMap = {
                'en': { text: 'Select Language', short: 'SELECT' },
                'ku': { text: 'زمان هەڵبژێرە', short: 'هەڵبژێرە' },
                'ar': { text: 'اختر اللغة', short: 'اختر' }
            };
            
            // Use current language to get the appropriate "Select Language" text
            const currentLanguageData = languageMap[currentLanguage];
            if (!currentLanguageData) {
                Logger.warning('Unknown language detected', currentLanguage);
                return;
            }
            
            // Always show "Select Language" text initially and clear any selections
            UIManager.updateLanguageInterface(currentLanguage, currentLanguageData.text, true);
            UIManager.clearLanguageOptionSelections(); // Clear any pre-selections
            Logger.info('Language selection prompt set', currentLanguage);
        } catch (error) {
            Logger.error('Failed to set language selection prompt', error);
        }
    }

    static _hideMainContent() {
        const elements = [
            document.querySelector('main'),
            document.querySelector('header'),
            document.querySelector('.category-navigation-section'),
            document.querySelector('footer')
        ];
        
        elements.forEach(element => {
            if (element) {
                element.style.opacity = '0';
                element.style.visibility = 'hidden';
                element.style.pointerEvents = 'none';
            }
        });
    }

    static _lockBodyScroll() {
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        document.body.style.height = '100%';
        document.body.style.top = '0';
        document.body.style.left = '0';
    }

    static _restoreBodyScroll() {
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
        document.body.style.height = '';
        document.body.style.top = '';
        document.body.style.left = '';
    }

    static _setupLanguageSelectionEvents() {
        const languageSelectButtons = document.querySelectorAll(DOM_SELECTORS.LANGUAGE_SELECT_BUTTONS);
        
        languageSelectButtons.forEach(button => {
            // Remove existing listeners by cloning
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                
                const languageCode = newButton.dataset.lang;
                const languageText = newButton.dataset.text;
                
                if (languageCode && languageText) {
                    this.processLanguageSelection(languageCode, languageText);
                } else {
                    Logger.error('Missing language data in selection button', { languageCode, languageText });
                }
            });
        });
        
        Logger.info(`Language selection buttons set up: ${languageSelectButtons.length}`);
    }

    static _preventEscapeClose(event) {
        if (event.key === 'Escape' && !appState.isLanguageSelectedInSession) {
            event.preventDefault();
            event.stopPropagation();
            Logger.info('Escape key prevented - language selection is mandatory');
        }
    }

    static _showWebsiteContent() {
        setTimeout(() => {
            const elements = [
                document.querySelector('main'),
                document.querySelector('header'),
                document.querySelector('.category-navigation-section'),
                document.querySelector('footer')
            ];
            
            elements.forEach(element => {
                if (element) {
                    element.style.transition = 'all 0.6s ease';
                    element.style.opacity = '1';
                    element.style.visibility = 'visible';
                    element.style.pointerEvents = 'auto';
                }
            });
            
            // Ensure smooth scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Initialize scroll reveal animations
            setTimeout(() => {
                EventManager._setupScrollRevealObserver();
            }, 300);
            
        }, 100);
    }
}

/**
 * ============================================================================
 * LANGUAGE POPUP MANAGER
 * ============================================================================
 */

class LanguagePopupManager {
    static openLanguagePopup() {
        try {
            const languagePopupOverlay = document.querySelector(DOM_SELECTORS.LANGUAGE_POPUP_OVERLAY);
            if (!languagePopupOverlay) {
                throw new Error('Language popup overlay not found');
            }

            languagePopupOverlay.classList.add(CSS_CLASSES.SHOW);
            document.body.classList.add(CSS_CLASSES.LANGUAGE_POPUP_OPEN);
            appState.isLanguagePopupOpen = true;
            
            // Only clear selections if no language has been selected yet
            // If a language is already selected, keep it highlighted in green
            if (!appState.isLanguageSelectedInSession) {
                UIManager.clearLanguageOptionSelections();
            }
            
            this._lockBodyScroll();
            this._setupLanguageOptionListeners();
            
            Logger.info('Language popup opened successfully');
        } catch (error) {
            Logger.error('Failed to open language popup', error);
        }
    }

    static closeLanguagePopup() {
        try {
            const languagePopupOverlay = document.querySelector(DOM_SELECTORS.LANGUAGE_POPUP_OVERLAY);
            if (languagePopupOverlay) {
                languagePopupOverlay.classList.remove(CSS_CLASSES.SHOW);
                document.body.classList.remove(CSS_CLASSES.LANGUAGE_POPUP_OPEN);
                appState.isLanguagePopupOpen = false;
                
                if (appState.isLanguageSelectedInSession) {
                    this._restoreBodyScroll();
                }
            }
            
            Logger.info('Language popup closed successfully');
        } catch (error) {
            Logger.error('Failed to close language popup', error);
        }
    }

    static _setupLanguageOptionListeners() {
        const languageOptions = document.querySelectorAll(DOM_SELECTORS.LANGUAGE_OPTIONS);
        
        languageOptions.forEach((option) => {
            const newOption = option.cloneNode(true);
            option.parentNode.replaceChild(newOption, option);
            
            newOption.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                
                const languageCode = newOption.dataset.lang;
                const languageText = newOption.dataset.text;
                
                if (languageCode && languageText) {
                    LanguageSelectionManager.processLanguageSelection(languageCode, languageText);
                } else {
                    Logger.error('Missing language data in option', { languageCode, languageText });
                }
            });
        });
        
        Logger.info(`Language option listeners set up for ${languageOptions.length} options`);
    }

    static _lockBodyScroll() {
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        document.body.style.height = '100%';
    }

    static _restoreBodyScroll() {
        document.body.style.overflow = 'auto';
        document.body.style.position = 'static';
        document.body.style.width = 'auto';
        document.body.style.height = 'auto';
    }
}

/**
 * ============================================================================
 * NAVIGATION LANGUAGE BUTTONS MANAGER - NEW
 * ============================================================================
 */

class NavigationLanguageButtonsManager {
    static initialize() {
        try {
            this._setupDesktopLanguageButtons();
            this._setupMobileLanguageButtons();
            this._setInitialActiveState();
            Logger.info('Navigation language buttons initialized successfully');
        } catch (error) {
            Logger.error('Failed to initialize navigation language buttons', error);
        }
    }

    static _setupDesktopLanguageButtons() {
        const desktopButtons = document.querySelectorAll(DOM_SELECTORS.DESKTOP_LANG_BUTTONS);
        
        desktopButtons.forEach(button => {
            // Remove existing listeners by cloning
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                
                const languageCode = newButton.dataset.lang;
                const languageText = newButton.dataset.text;
                
                if (languageCode && languageText) {
                    this._handleLanguageButtonClick(languageCode, languageText);
                } else {
                    Logger.error('Missing language data in desktop button', { languageCode, languageText });
                }
            });
        });
        
        Logger.info(`Desktop language buttons set up: ${desktopButtons.length}`);
    }

    static _setupMobileLanguageButtons() {
        const mobileButtons = document.querySelectorAll(DOM_SELECTORS.MOBILE_LANG_BUTTONS);
        
        mobileButtons.forEach(button => {
            // Remove existing listeners by cloning
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                
                const languageCode = newButton.dataset.lang;
                const languageText = newButton.dataset.text;
                
                if (languageCode && languageText) {
                    this._handleLanguageButtonClick(languageCode, languageText);
                    
                    // Close mobile menu if open
                    if (appState.isMobileMenuOpen) {
                        MobileMenuManager.closeMobileMenu();
                    }
                } else {
                    Logger.error('Missing language data in mobile button', { languageCode, languageText });
                }
            });
        });
        
        Logger.info(`Mobile language buttons set up: ${mobileButtons.length}`);
    }

    static _handleLanguageButtonClick(languageCode, languageText) {
        try {
            // Mark language as selected in session
            appState.isLanguageSelectedInSession = true;
            
            // Update button states immediately
            UIManager.updateNavigationLanguageButtons(languageCode);
            
            const currentLanguage = LanguageManager.getCurrentLanguage();
            Logger.info('Navigation language button clicked', { current: currentLanguage, selected: languageCode });
            
            if (languageCode !== currentLanguage) {
                // Navigate to different language page
                LanguageManager.navigateToLanguage(languageCode);
            } else {
                // Same language - just update session
                SessionManager.setLanguageSelection(languageCode);
                Logger.info('Same language selected - session updated');
            }
        } catch (error) {
            Logger.error('Error handling language button click', error);
        }
    }

    static _setInitialActiveState() {
        try {
            const currentLanguage = LanguageManager.getCurrentLanguage();
            
            // Check if language was selected in session
            const hasSelectedInSession = SessionManager.hasLanguageSelection();
            const selectedLanguage = SessionManager.getSelectedLanguage();
            
            if (hasSelectedInSession && selectedLanguage) {
                // Use the language from session
                UIManager.updateNavigationLanguageButtons(selectedLanguage);
                Logger.info('Set navigation buttons from session language:', selectedLanguage);
            } else {
                // Set based on current page language
                UIManager.updateNavigationLanguageButtons(currentLanguage);
                Logger.info('Set navigation buttons from current page language:', currentLanguage);
            }
        } catch (error) {
            Logger.error('Failed to set initial navigation button state', error);
        }
    }
}

/**
 * ============================================================================
 * MOBILE MENU MANAGER
 * ============================================================================
 */

class MobileMenuManager {
    static initialize() {
        try {
            const mobileMenuToggle = document.querySelector(DOM_SELECTORS.MOBILE_MENU_TOGGLE);
            const mobileMenuOverlay = document.querySelector(DOM_SELECTORS.MOBILE_MENU_OVERLAY);
            const mobileMenuClose = document.querySelector(DOM_SELECTORS.MOBILE_MENU_CLOSE);

            if (!mobileMenuToggle || !mobileMenuOverlay) {
                Logger.warning('Mobile menu elements not found - mobile menu disabled');
                return;
            }

            this._setupEventListeners(mobileMenuToggle, mobileMenuOverlay, mobileMenuClose);
            this._setupGlobalListeners();
            
            Logger.info('Mobile menu initialized successfully');
        } catch (error) {
            Logger.error('Failed to initialize mobile menu', error);
        }
    }

    static openMobileMenu() {
        try {
            const mobileMenuOverlay = document.querySelector(DOM_SELECTORS.MOBILE_MENU_OVERLAY);
            const header = document.querySelector('header');
            const categoryNavigation = document.querySelector('.category-navigation-section');
            
            appState.savedScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            
            mobileMenuOverlay.classList.remove(CSS_CLASSES.HIDDEN);
            mobileMenuOverlay.classList.add(CSS_CLASSES.SHOW);
            
            if (header) header.classList.add(CSS_CLASSES.MOBILE_HIDDEN);
            if (categoryNavigation) categoryNavigation.classList.add(CSS_CLASSES.MOBILE_HIDDEN);
            
            this._lockBodyScroll();
            this._updateToggleIcon('fas fa-times');
            
            appState.isMobileMenuOpen = true;
            Logger.info('Mobile menu opened successfully');
        } catch (error) {
            Logger.error('Failed to open mobile menu', error);
        }
    }

    static closeMobileMenu() {
        try {
            const mobileMenuOverlay = document.querySelector(DOM_SELECTORS.MOBILE_MENU_OVERLAY);
            const header = document.querySelector('header');
            const categoryNavigation = document.querySelector('.category-navigation-section');
            
            if (mobileMenuOverlay) {
                mobileMenuOverlay.classList.add(CSS_CLASSES.HIDDEN);
                mobileMenuOverlay.classList.remove(CSS_CLASSES.SHOW);
            }
            
            if (header) header.classList.remove(CSS_CLASSES.MOBILE_HIDDEN);
            if (categoryNavigation) categoryNavigation.classList.remove(CSS_CLASSES.MOBILE_HIDDEN);
            
            this._restoreBodyScroll();
            this._updateToggleIcon('fas fa-bars');
            
            appState.isMobileMenuOpen = false;
            Logger.info('Mobile menu closed successfully');
        } catch (error) {
            Logger.error('Failed to close mobile menu', error);
        }
    }

    static _setupEventListeners(toggleButton, overlay, closeButton) {
        toggleButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            
            if (overlay.classList.contains(CSS_CLASSES.HIDDEN)) {
                this.openMobileMenu();
            } else {
                this.closeMobileMenu();
            }
        });

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                this.closeMobileMenu();
            }
        });

        if (closeButton) {
            closeButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                this.closeMobileMenu();
            });
        }

        this._setupMenuLinks(overlay);
    }

    static _setupGlobalListeners() {
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && appState.isMobileMenuOpen) {
                this.closeMobileMenu();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > APP_CONFIG.MOBILE_BREAKPOINT && appState.isMobileMenuOpen) {
                this.closeMobileMenu();
            }
        });
    }

    static _setupMenuLinks(overlay) {
        const socialLinks = overlay.querySelectorAll('.social-link-mobile');
        const phoneLink = overlay.querySelector('a[href^="tel:"]');
        
        socialLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.closeMobileMenu();
            });
        });
        
        if (phoneLink) {
            phoneLink.addEventListener('click', () => {
                this.closeMobileMenu();
            });
        }
    }

    static _lockBodyScroll() {
        document.body.classList.add(CSS_CLASSES.MOBILE_MENU_OPEN);
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${appState.savedScrollPosition}px`;
        document.body.style.width = '100%';
        document.body.style.height = '100%';
    }

    static _restoreBodyScroll() {
        document.body.classList.remove(CSS_CLASSES.MOBILE_MENU_OPEN);
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        document.body.style.height = '';
        
        window.scrollTo(0, appState.savedScrollPosition);
    }

    static _updateToggleIcon(iconClass) {
        const toggleButton = document.querySelector(DOM_SELECTORS.MOBILE_MENU_TOGGLE);
        const icon = toggleButton?.querySelector('i');
        if (icon) {
            icon.className = iconClass;
        }
    }
}

/**
 * ============================================================================
 * MENU RENDERER
 * ============================================================================
 */

class MenuRenderer {
    static renderAllMenuItems() {
        try {
            document.querySelectorAll('.menu-items-grid').forEach(grid => {
                grid.innerHTML = '';
            });

            for (const categoryId in appState.menuData) {
                this._renderCategoryItems(categoryId);
            }
            
            Logger.info('All menu items rendered successfully');
        } catch (error) {
            Logger.error('Failed to render menu items', error);
        }
    }

    static _renderCategoryItems(categoryId) {
        const categoryItems = appState.menuData[categoryId];
        const container = document.querySelector(`#${categoryId} .menu-items-grid`);
        const section = document.getElementById(categoryId);
        
        if (!container || !section) {
            Logger.warning(`Container or section not found for category: ${categoryId}`);
            return;
        }

        if (Object.keys(categoryItems).length > 0) {
            section.style.display = 'block';
            
            for (const itemId in categoryItems) {
                const item = categoryItems[itemId];
                const itemCard = this._createMenuItemCard(item, categoryId, itemId);
                container.appendChild(itemCard);
            }
        } else {
            section.style.display = 'none';
        }
    }

    static _createMenuItemCard(item, categoryId, itemId) {
        const card = document.createElement('div');
        card.className = 'menu-item-card scroll-reveal';
        card.setAttribute('data-category-id', categoryId);
        card.setAttribute('data-item-id', itemId);

        card.innerHTML = `
            <div class="card-image-container">
                <img src="${this._sanitizeImageUrl(item.image)}" 
                     alt="${this._sanitizeText(item.name)}" 
                     class="menu-item-image">
            </div>
            <div class="card-content">
                <div class="card-info">
                    <h3 class="item-name">${this._sanitizeText(item.name)}</h3>
                    <p class="item-price">${this._sanitizeText(item.price)}</p>
                </div>
            </div>
        `;
        
        card.addEventListener('click', () => {
            ModalManager.openFoodModal({
                image: item.image,
                alt: item.name,
                title: item.name,
                description: item.description,
                price: item.price
            });
        });

        return card;
    }

    static _sanitizeImageUrl(url) {
        try {
            const sanitized = normalizeAssetUrl(url).replace(/[<>"']/g, '');
            return sanitized.startsWith('img-champions/') ||
                sanitized.startsWith('/') ||
                sanitized.startsWith('http')
                ? sanitized
                : 'img-champions/champions-cafe-shisha-logo.png';
        } catch (error) {
            Logger.error('Error sanitizing image URL', error);
            return 'img-champions/champions-cafe-shisha-logo.png';
        }
    }

    static _sanitizeText(text) {
        try {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        } catch (error) {
            Logger.error('Error sanitizing text', error);
            return '';
        }
    }
}

/**
 * ============================================================================
 * MODAL MANAGER WITH DRAG-TO-CLOSE FUNCTIONALITY
 * ============================================================================
 */

class ModalManager {
    static openFoodModal(itemData) {
        try {
            this._validateItemData(itemData);
            
            const modal = document.querySelector(DOM_SELECTORS.FOOD_MODAL);
            if (!modal) {
                throw new Error('Food modal element not found');
            }

            this._populateModalContent(itemData);
            this._setupDragToClose(modal);
            
            modal.classList.add(CSS_CLASSES.SHOW);
            document.body.style.overflow = 'hidden';
            
            // Reset modal position and state
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.transform = '';
                modalContent.style.opacity = '';
                modalContent.classList.remove(CSS_CLASSES.DRAGGING);
            }
            
            Logger.info('Food modal opened successfully');
        } catch (error) {
            Logger.error('Failed to open food modal', error);
        }
    }

    static closeFoodModal() {
        try {
            const modal = document.querySelector(DOM_SELECTORS.FOOD_MODAL);
            if (modal) {
                const modalContent = modal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.classList.remove(CSS_CLASSES.DRAGGING);
                    modalContent.style.transform = '';
                    modalContent.style.opacity = '';
                }
                
                modal.classList.remove(CSS_CLASSES.SHOW);
                document.body.style.overflow = 'auto';
                
                // Clean up drag listeners
                this._removeDragListeners();
            }
            
            // Reset drag state
            appState.modalDragState = {
                isDragging: false,
                startY: 0,
                currentY: 0,
                startTime: 0,
                lastY: 0,
                lastTime: 0,
                velocity: 0
            };
            
            Logger.info('Food modal closed successfully');
        } catch (error) {
            Logger.error('Failed to close food modal', error);
        }
    }

    static initialize() {
        try {
            const modal = document.querySelector(DOM_SELECTORS.FOOD_MODAL);
            if (modal) {
                modal.addEventListener('click', (event) => {
                    if (event.target.id === 'foodModal' || 
                        event.target.classList.contains('close-modal')) {
                        this.closeFoodModal();
                    }
                });
            }
            
            Logger.info('Modal manager initialized successfully');
        } catch (error) {
            Logger.error('Failed to initialize modal manager', error);
        }
    }

    static _setupDragToClose(modal) {
        const modalContent = modal.querySelector('.modal-content');
        if (!modalContent) return;

        // Create drag handle area (top portion of modal)
        const dragHandle = modalContent.querySelector('.modal-image-container') || modalContent;
        
        // Touch events
        dragHandle.addEventListener('touchstart', this._handleDragStart.bind(this), { passive: false });
        dragHandle.addEventListener('touchmove', this._handleDragMove.bind(this), { passive: false });
        dragHandle.addEventListener('touchend', this._handleDragEnd.bind(this), { passive: false });
        
        // Mouse events for desktop testing
        dragHandle.addEventListener('mousedown', this._handleDragStart.bind(this));
        
        // Prevent default touch behaviors on the drag handle
        dragHandle.style.touchAction = 'pan-y';
        dragHandle.style.userSelect = 'none';
    }

    static _handleDragStart(event) {
        const modal = document.querySelector(DOM_SELECTORS.FOOD_MODAL);
        const modalContent = modal?.querySelector('.modal-content');
        if (!modalContent) return;

        // Only allow dragging if the modal is fully open
        if (!modal.classList.contains(CSS_CLASSES.SHOW)) return;

        const clientY = event.type.includes('touch') ? event.touches[0].clientY : event.clientY;
        const now = Date.now();

        appState.modalDragState = {
            isDragging: true,
            startY: clientY,
            currentY: clientY,
            startTime: now,
            lastY: clientY,
            lastTime: now,
            velocity: 0
        };

        modalContent.classList.add(CSS_CLASSES.DRAGGING);
        
        // Add move and end listeners
        if (event.type.includes('touch')) {
            document.addEventListener('touchmove', this._handleDragMove.bind(this), { passive: false });
            document.addEventListener('touchend', this._handleDragEnd.bind(this), { passive: false });
        } else {
            document.addEventListener('mousemove', this._handleDragMove.bind(this));
            document.addEventListener('mouseup', this._handleDragEnd.bind(this));
        }

        event.preventDefault();
    }

    static _handleDragMove(event) {
        if (!appState.modalDragState.isDragging) return;

        const modal = document.querySelector(DOM_SELECTORS.FOOD_MODAL);
        const modalContent = modal?.querySelector('.modal-content');
        if (!modalContent) return;

        const clientY = event.type.includes('touch') ? event.touches[0].clientY : event.clientY;
        const now = Date.now();
        
        // Calculate drag distance (only allow downward dragging)
        const deltaY = Math.max(0, clientY - appState.modalDragState.startY);
        
        // Calculate velocity
        const timeDelta = now - appState.modalDragState.lastTime;
        if (timeDelta > 0) {
            const yDelta = clientY - appState.modalDragState.lastY;
            appState.modalDragState.velocity = yDelta / timeDelta;
        }
        
        appState.modalDragState.currentY = clientY;
        appState.modalDragState.lastY = clientY;
        appState.modalDragState.lastTime = now;

        // Apply transform and opacity changes
        const progress = Math.min(deltaY / APP_CONFIG.MODAL_DRAG.CLOSE_THRESHOLD, 1);
        const opacity = Math.max(APP_CONFIG.MODAL_DRAG.OPACITY_MIN, 1 - progress * 0.7);
        
        modalContent.style.transform = `translateY(${deltaY}px)`;
        modalContent.style.opacity = opacity;
        modal.style.backgroundColor = `rgba(0, 0, 0, ${0.6 * opacity})`;

        event.preventDefault();
    }

    static _handleDragEnd(event) {
        if (!appState.modalDragState.isDragging) return;

        const modal = document.querySelector(DOM_SELECTORS.FOOD_MODAL);
        const modalContent = modal?.querySelector('.modal-content');
        if (!modalContent) return;

        const deltaY = Math.max(0, appState.modalDragState.currentY - appState.modalDragState.startY);
        const velocity = appState.modalDragState.velocity;

        // Determine if modal should close
        const shouldClose = deltaY > APP_CONFIG.MODAL_DRAG.CLOSE_THRESHOLD || 
                           velocity > APP_CONFIG.MODAL_DRAG.VELOCITY_THRESHOLD;

        modalContent.classList.remove(CSS_CLASSES.DRAGGING);
        
        if (shouldClose) {
            // Animate close
            modalContent.style.transition = `transform ${APP_CONFIG.MODAL_DRAG.ANIMATION_DURATION}ms ease-out`;
            modalContent.style.transform = 'translateY(100vh)';
            modalContent.style.opacity = '0';
            
            setTimeout(() => {
                this.closeFoodModal();
                modalContent.style.transition = '';
            }, APP_CONFIG.MODAL_DRAG.ANIMATION_DURATION);
        } else {
            // Snap back to original position
            modalContent.style.transition = `all ${APP_CONFIG.MODAL_DRAG.ANIMATION_DURATION}ms cubic-bezier(0.4, 0, 0.2, 1)`;
            modalContent.style.transform = '';
            modalContent.style.opacity = '';
            modal.style.backgroundColor = '';
            
            setTimeout(() => {
                modalContent.style.transition = '';
            }, APP_CONFIG.MODAL_DRAG.ANIMATION_DURATION);
        }

        // Clean up listeners
        this._removeDragListeners();
        
        // Reset drag state
        appState.modalDragState.isDragging = false;

        event.preventDefault();
    }

    static _removeDragListeners() {
        document.removeEventListener('touchmove', this._handleDragMove.bind(this));
        document.removeEventListener('touchend', this._handleDragEnd.bind(this));
        document.removeEventListener('mousemove', this._handleDragMove.bind(this));
        document.removeEventListener('mouseup', this._handleDragEnd.bind(this));
    }

    static _validateItemData(itemData) {
        // A description is optional (for example, Shisha items only need a
        // name, image, and price). Do not block the modal when it is empty.
        const requiredFields = ['image', 'title', 'price'];
        for (const field of requiredFields) {
            if (!itemData[field]) {
                throw new Error(`Missing required field: ${field}`);
            }
        }
    }

    static _populateModalContent(itemData) {
        const elements = {
            modalImage: document.getElementById('modalImage'),
            modalTitle: document.getElementById('modalTitle'),
            modalDescription: document.getElementById('modalDescription'),
            modalPrice: document.getElementById('modalPrice')
        };

        if (elements.modalImage) {
            elements.modalImage.src = normalizeAssetUrl(itemData.image);
            elements.modalImage.alt = itemData.alt;
        }
        if (elements.modalTitle) {
            elements.modalTitle.textContent = itemData.title;
        }
        if (elements.modalDescription) {
            elements.modalDescription.textContent = itemData.description;
        }
        if (elements.modalPrice) {
            elements.modalPrice.textContent = itemData.price;
        }
    }
}

/**
 * ============================================================================
 * CATEGORY NAVIGATION MANAGER
 * ============================================================================
 */

class CategoryNavigationManager {
    static initialize() {
        try {
            const categoryButtons = document.querySelectorAll(DOM_SELECTORS.CATEGORY_BUTTONS);
            const categoriesContainer = document.querySelector(DOM_SELECTORS.CATEGORIES_GRID);
            
            if (categoryButtons.length === 0) {
                Logger.warning('No category buttons found - navigation disabled');
                return;
            }

            this._setupCategoryButtons(categoryButtons, categoriesContainer);
            this._setupScrollObserver();
            this._setupUserInteractionTracking();
            
            Logger.info('Category navigation initialized successfully');
        } catch (error) {
            Logger.error('Failed to initialize category navigation', error);
        }
    }

    static _setupCategoryButtons(categoryButtons, categoriesContainer) {
        categoryButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                
                const targetId = button.getAttribute('data-target');
                
                categoryButtons.forEach(btn => btn.classList.remove(CSS_CLASSES.ACTIVE));
                button.classList.add(CSS_CLASSES.ACTIVE);
                button.classList.add(CSS_CLASSES.USER_CLICKED);
                
                this._scrollToSection(targetId);
                this._scrollToActiveCategory(button, categoriesContainer);
            });
        });
    }

    static _setupScrollObserver() {
        const sections = document.querySelectorAll(DOM_SELECTORS.MENU_SECTIONS);
        const categoryButtons = document.querySelectorAll(DOM_SELECTORS.CATEGORY_BUTTONS);
        
        const observer = new IntersectionObserver((entries) => {
            // Find the most visible section
            let mostVisible = null;
            let maxRatio = 0;
            
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.intersectionRatio > maxRatio) {
                    maxRatio = entry.intersectionRatio;
                    mostVisible = entry.target;
                }
            });
            
            // Update active category only if we have a clear winner and user hasn't clicked recently
            if (mostVisible && maxRatio > 0.3 && !this._hasUserClickedRecently(categoryButtons)) {
                const sectionId = mostVisible.id;
                const correspondingButton = document.querySelector(`[data-target="${sectionId}"]`);
                
                if (correspondingButton) {
                    // Remove active class from all buttons
                    categoryButtons.forEach(btn => btn.classList.remove(CSS_CLASSES.ACTIVE));
                    
                    // Add active class to current button
                    correspondingButton.classList.add(CSS_CLASSES.ACTIVE);
                    
                    // Scroll category navigation to show active button
                    this._scrollToActiveCategory(correspondingButton);
                }
            }
        }, {
            threshold: [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8],
            rootMargin: '-80px 0px -300px 0px'
        });
        
        sections.forEach(section => observer.observe(section));
        
        // Also add a backup scroll listener for better responsiveness
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                if (!this._hasUserClickedRecently(categoryButtons)) {
                    this._updateActiveBasedOnScroll(sections, categoryButtons);
                }
            }, 100);
        });
    }

    static _setupUserInteractionTracking() {
        let clearTimer;
        window.addEventListener('scroll', () => {
            clearTimeout(clearTimer);
            clearTimer = setTimeout(() => {
                const categoryButtons = document.querySelectorAll(DOM_SELECTORS.CATEGORY_BUTTONS);
                categoryButtons.forEach(btn => btn.classList.remove(CSS_CLASSES.USER_CLICKED));
            }, 1500);
        });
    }

    static _scrollToSection(sectionId) {
        const targetSection = document.getElementById(sectionId);
        if (!targetSection) {
            Logger.warning(`Section not found: ${sectionId}`);
            return;
        }
        
        const header = document.querySelector('header');
        const categoryNav = document.querySelector('.category-navigation-section');
        let offset = APP_CONFIG.SCROLL_OFFSET;
        
        if (header) offset += 80;
        if (categoryNav) offset += 120;
        
        const targetY = targetSection.offsetTop - offset;
        const finalY = Math.max(0, targetY);
        
        window.scrollTo({ top: finalY, behavior: 'smooth' });
    }

    static _updateActiveBasedOnScroll(sections, categoryButtons) {
        const scrollPosition = window.scrollY + 200; // Offset for fixed headers
        let activeSection = null;
        
        sections.forEach(section => {
            const rect = section.getBoundingClientRect();
            const sectionTop = rect.top + window.scrollY;
            const sectionBottom = sectionTop + rect.height;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                activeSection = section;
            }
        });
        
        if (activeSection) {
            const sectionId = activeSection.id;
            const correspondingButton = document.querySelector(`[data-target="${sectionId}"]`);
            
            if (correspondingButton) {
                categoryButtons.forEach(btn => btn.classList.remove(CSS_CLASSES.ACTIVE));
                correspondingButton.classList.add(CSS_CLASSES.ACTIVE);
                this._scrollToActiveCategory(correspondingButton);
            }
        }
    }

    static _scrollToActiveCategory(activeButton, container) {
        if (!activeButton) return;
        
        // Find the categories container
        const categoriesContainer = container || document.querySelector(DOM_SELECTORS.CATEGORIES_GRID);
        if (!categoriesContainer) return;
        
        const containerRect = categoriesContainer.getBoundingClientRect();
        const buttonRect = activeButton.getBoundingClientRect();
        const containerScrollLeft = categoriesContainer.scrollLeft;
        
        // Calculate button position relative to container
        const buttonRelativeLeft = buttonRect.left - containerRect.left + containerScrollLeft;
        const buttonRelativeRight = buttonRelativeLeft + buttonRect.width;
        
        // Check if button is outside visible area
        const containerWidth = containerRect.width;
        const scrollLeft = containerScrollLeft;
        const scrollRight = scrollLeft + containerWidth;
        
        let targetScrollLeft = containerScrollLeft;
        
        // If button is to the left of visible area
        if (buttonRelativeLeft < scrollLeft) {
            targetScrollLeft = buttonRelativeLeft - 50; // Add some margin
        }
        // If button is to the right of visible area
        else if (buttonRelativeRight > scrollRight) {
            targetScrollLeft = buttonRelativeRight - containerWidth + 50; // Add some margin
        }
        
        // Only scroll if needed
        if (targetScrollLeft !== containerScrollLeft) {
            categoriesContainer.scrollTo({
                left: Math.max(0, targetScrollLeft),
                behavior: 'smooth'
            });
        }
    }

    static _hasUserClickedRecently(categoryButtons) {
        return Array.from(categoryButtons).some(btn => 
            btn.classList.contains(CSS_CLASSES.USER_CLICKED)
        );
    }
}

/**
 * ============================================================================
 * DEVICE MANAGER - SIMPLIFIED (NO ORIENTATION RESTRICTIONS)
 * ============================================================================
 */

class DeviceManager {
    static isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
               window.innerWidth <= APP_CONFIG.MOBILE_BREAKPOINT;
    }

    static optimizeViewport() {
        try {
            const metaViewport = document.querySelector('meta[name="viewport"]');
            if (metaViewport) {
                let content = metaViewport.getAttribute('content');
                if (!content.includes('user-scalable')) {
                    content += ', user-scalable=no';
                }
                if (!content.includes('maximum-scale')) {
                    content += ', maximum-scale=1.0';
                }
                metaViewport.setAttribute('content', content);
            }
            
            document.body.style.overscrollBehavior = 'none';
            Logger.info('Viewport optimized for all orientations');
        } catch (error) {
            Logger.error('Failed to optimize viewport', error);
        }
    }
}

/**
 * ============================================================================
 * EVENT MANAGER
 * ============================================================================
 */

class EventManager {
    static setupAllEventListeners() {
        try {
            this._setupLanguagePopupListeners();
            this._setupScrollRevealObserver();
            this._setupWindowResizeListener();
            
            Logger.info('All event listeners set up successfully');
        } catch (error) {
            Logger.error('Failed to set up event listeners', error);
        }
    }

    static _setupLanguagePopupListeners() {
        const elements = {
            languagePopupOverlay: document.querySelector(DOM_SELECTORS.LANGUAGE_POPUP_OVERLAY),
            languagePopupClose: document.getElementById('languagePopupClose'),
            navbarLanguageBtn: document.querySelector(DOM_SELECTORS.NAVBAR_LANGUAGE_BUTTON),
            mobileLanguageBtn: document.querySelector(DOM_SELECTORS.MOBILE_LANGUAGE_BUTTON)
        };

        if (elements.languagePopupClose) {
            elements.languagePopupClose.addEventListener('click', (event) => {
                if (appState.isLanguageSelectedInSession) {
                    LanguagePopupManager.closeLanguagePopup();
                } else {
                    event.preventDefault();
                    Logger.info('Language selection is mandatory - close prevented');
                }
            });
        }

        if (elements.languagePopupOverlay) {
            elements.languagePopupOverlay.addEventListener('click', (event) => {
                if (event.target === elements.languagePopupOverlay && 
                    appState.isLanguageSelectedInSession) {
                    LanguagePopupManager.closeLanguagePopup();
                } else {
                    event.preventDefault();
                    Logger.info('Language selection is mandatory - overlay close prevented');
                }
            });
        }

        if (elements.navbarLanguageBtn) {
            elements.navbarLanguageBtn.addEventListener('click', () => {
                LanguagePopupManager.openLanguagePopup();
            });
        }

        if (elements.mobileLanguageBtn) {
            elements.mobileLanguageBtn.addEventListener('click', () => {
                LanguagePopupManager.openLanguagePopup();
                if (appState.isMobileMenuOpen) {
                    MobileMenuManager.closeMobileMenu();
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && 
                elements.languagePopupOverlay?.classList.contains(CSS_CLASSES.SHOW)) {
                if (appState.isLanguageSelectedInSession) {
                    LanguagePopupManager.closeLanguagePopup();
                } else {
                    event.preventDefault();
                    Logger.info('Language selection is mandatory - escape prevented');
                }
            }
        });
    }

    static _setupScrollRevealObserver() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add(CSS_CLASSES.REVEALED);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.scroll-reveal').forEach(element => {
            observer.observe(element);
        });
    }

    static _setupWindowResizeListener() {
        window.addEventListener('resize', () => {
            setTimeout(() => {
                CategoryNavigationManager.initialize();
            }, 100);
        });
    }
}

/**
 * ============================================================================
 * APPLICATION INITIALIZER - FIXED FOR PROPER WEBSITE FLOW
 * ============================================================================
 */

class ApplicationInitializer {
    static async initialize() {
        try {
            Logger.info('Starting application initialization', APP_CONFIG.VERSION);

            // Check if language was selected in current session
            const hasSelectedInSession = SessionManager.hasLanguageSelection();
            
            if (hasSelectedInSession) {
                appState.isLanguageSelectedInSession = true;
                const selectedLanguage = SessionManager.getSelectedLanguage();
                
                // Update navigation to show the selected language and mark it as selected
                if (selectedLanguage) {
                    const languageMap = {
                        'en': 'English',
                        'ku': 'کوردی', 
                        'ar': 'العربية'
                    };
                    const languageText = languageMap[selectedLanguage];
                    if (languageText) {
                        UIManager.updateLanguageInterface(selectedLanguage, languageText, false);
                        UIManager.markLanguageAsSelected(selectedLanguage); // Keep it highlighted in dropdown
                        UIManager.updateNavigationLanguageButtons(selectedLanguage); // Update navigation buttons
                    }
                }
                
                // Proceed directly to website for returning users
                LanguageSelectionManager.proceedToWebsite();
                Logger.info('Language already selected in session - proceeding to website');
            } else {
                // Show language selection screen for new visits
                LanguageSelectionManager.showMandatoryLanguageSelection();
                Logger.info('New visit - showing mandatory language selection screen');
            }

            await this._initializeCoreModules();
            await this._initializeUIComponents();
            await this._initializeDeviceFeatures();
            
            EventManager.setupAllEventListeners();
            
            // Only set the "Select Language" prompt if no language was previously selected
            if (!hasSelectedInSession) {
                setTimeout(() => {
                    LanguageSelectionManager.setCurrentLanguageSelection();
                }, 100);
            }

            Logger.info('Application initialization completed successfully');
        } catch (error) {
            Logger.error('Application initialization failed', error);
            this._handleInitializationError(error);
        }
    }

    static async _initializeCoreModules() {
        TranslationManager.initialize();
        MenuDataManager.initialize();
        Logger.info('Core modules initialized');
    }

    static async _initializeUIComponents() {
        MenuRenderer.renderAllMenuItems();
        CategoryNavigationManager.initialize();
        MobileMenuManager.initialize();
        ModalManager.initialize();
        FeedbackManager.initialize();
        NavigationLanguageButtonsManager.initialize(); // NEW: Initialize navigation language buttons
        Logger.info('UI components initialized');
    }

    static async _initializeDeviceFeatures() {
        if (DeviceManager.isMobileDevice()) {
            DeviceManager.optimizeViewport();
            Logger.info('Mobile device features initialized (all orientations supported)');
        }
    }

    static _handleInitializationError(error) {
        const errorMessage = document.createElement('div');
        errorMessage.style.cssText = `
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: #ff4444; color: white; padding: 2rem; border-radius: 8px;
            font-family: Arial, sans-serif; text-align: center; z-index: 10000;
        `;
        errorMessage.innerHTML = `
            <h3>Application Error</h3>
            <p>The application failed to initialize properly. Please refresh the page.</p>
            <button onclick="window.location.reload()" 
                    style="background: white; color: #ff4444; border: none; padding: 0.5rem 1rem; 
                           border-radius: 4px; cursor: pointer; margin-top: 1rem;">
                Refresh Page
            </button>
        `;
        document.body.appendChild(errorMessage);
    }
}

/**
 * ============================================================================
 * APPLICATION ENTRY POINT
 * ============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('img[src]').forEach((image) => {
        image.setAttribute('src', normalizeAssetUrl(image.getAttribute('src')));
    });
    ApplicationInitializer.initialize();
});

/**
 * ============================================================================
 * END OF APPLICATION
 * ============================================================================ */
