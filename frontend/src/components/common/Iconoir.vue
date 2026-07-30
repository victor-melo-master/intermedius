<template>
  <component :is="component" class="w-5 h-5" />
</template>

<script setup>
import { computed } from 'vue'
import HeroiconsChartBar from '~icons/heroicons/chart-bar'
import HeroiconsDocumentText from '~icons/heroicons/document-text'
import HeroiconsUsers from '~icons/heroicons/users'
import HeroiconsBuildingLibrary from '~icons/heroicons/building-library'
import HeroiconsArrowTrendingDown from '~icons/heroicons/arrow-trending-down'
import HeroiconsCurrencyDollar from '~icons/heroicons/currency-dollar'
import HeroiconsBanknotes from '~icons/heroicons/banknotes'
import HeroiconsShoppingCart from '~icons/heroicons/shopping-cart'
import HeroiconsKey from '~icons/heroicons/key'
import HeroiconsMagnifyingGlass from '~icons/heroicons/magnifying-glass'
import HeroiconsXMark from '~icons/heroicons/x-mark'
import HeroiconsCheck from '~icons/heroicons/check'
import HeroiconsTrash from '~icons/heroicons/trash'
import HeroiconsPencilSquare from '~icons/heroicons/pencil-square'
import HeroiconsClipboard from '~icons/heroicons/clipboard'
import HeroiconsArrowLeft from '~icons/heroicons/arrow-left'
import HeroiconsArrowRight from '~icons/heroicons/arrow-right'
import HeroiconsArrowUturnLeft from '~icons/heroicons/arrow-uturn-left'
import HeroiconsArrowPath from '~icons/heroicons/arrow-path'
import HeroiconsExclamationTriangle from '~icons/heroicons/exclamation-triangle'
import HeroiconsLockClosed from '~icons/heroicons/lock-closed'
import HeroiconsRocketLaunch from '~icons/heroicons/rocket-launch'
import HeroiconsBell from '~icons/heroicons/bell'
import HeroiconsLink from '~icons/heroicons/link'
import HeroiconsEnvelopeOpen from '~icons/heroicons/envelope-open'
import HeroiconsIdentification from '~icons/heroicons/identification'
import HeroiconsQueueList from '~icons/heroicons/queue-list'
import HeroiconsArrowsRightLeft from '~icons/heroicons/arrows-right-left'
import HeroiconsCheckCircle from '~icons/heroicons/check-circle'
import HeroiconsFolderOpen from '~icons/heroicons/folder-open'
import HeroiconsArrowTrendingUp from '~icons/heroicons/arrow-trending-up'
import HeroiconsHome from '~icons/heroicons/home'
import HeroiconsDocument from '~icons/heroicons/document'
import HeroiconsReceiptPercent from '~icons/heroicons/receipt-percent'
import HeroiconsCreditCard from '~icons/heroicons/credit-card'
import HeroiconsUsersSolid from '~icons/heroicons/users-solid'

const map = {
  'chart-bar': HeroiconsChartBar,
  'document-text': HeroiconsDocumentText,
  'users': HeroiconsUsers,
  'building-library': HeroiconsBuildingLibrary,
  'arrow-trending-down': HeroiconsArrowTrendingDown,
  'currency-dollar': HeroiconsCurrencyDollar,
  'banknotes': HeroiconsBanknotes,
  'shopping-cart': HeroiconsShoppingCart,
  'key': HeroiconsKey,
  'magnifying-glass': HeroiconsMagnifyingGlass,
  'x-mark': HeroiconsXMark,
  'check': HeroiconsCheck,
  'trash': HeroiconsTrash,
  'pencil-square': HeroiconsPencilSquare,
  'clipboard': HeroiconsClipboard,
  'arrow-left': HeroiconsArrowLeft,
  'arrow-right': HeroiconsArrowRight,
  'arrow-uturn-left': HeroiconsArrowUturnLeft,
  'arrow-path': HeroiconsArrowPath,
  'exclamation-triangle': HeroiconsExclamationTriangle,
  'lock-closed': HeroiconsLockClosed,
  'rocket-launch': HeroiconsRocketLaunch,
  'bell': HeroiconsBell,
  'link': HeroiconsLink,
  'envelope-open': HeroiconsEnvelopeOpen,
  'identification': HeroiconsIdentification,
  'queue-list': HeroiconsQueueList,
  'arrows-right-left': HeroiconsArrowsRightLeft,
  'check-circle': HeroiconsCheckCircle,
  'folder-open': HeroiconsFolderOpen,
  'arrow-trending-up': HeroiconsArrowTrendingUp,
  'home': HeroiconsHome,
  'document': HeroiconsDocument,
  'receipt-percent': HeroiconsReceiptPercent,
  'credit-card': HeroiconsCreditCard,
  'users-solid': HeroiconsUsersSolid,
}

const props = defineProps({
  name: { type: String, required: true },
})

const component = computed(() => map[props.name])
</script>
