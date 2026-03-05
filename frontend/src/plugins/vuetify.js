import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

const greenPilotTheme = {
  dark: false,
  colors: {
    primary: '#2E7D32',
    'primary-darken-1': '#1B5E20',
    'primary-lighten-1': '#43A047',
    'primary-lighten-2': '#66BB6A',
    secondary: '#00796B',
    'secondary-lighten-1': '#26A69A',
    accent: '#00C853',
    error: '#D32F2F',
    warning: '#F57C00',
    info: '#1976D2',
    success: '#388E3C',
    background: '#FAFBFC',
    surface: '#FFFFFF',
    'surface-variant': '#F1F5F1',
    'surface-light': '#F5F8F5',
    'on-background': '#1A1A2E',
    'on-surface': '#1A1A2E',
  },
  variables: {
    'shadow-key-umbra-opacity': 'rgba(0,0,0,0.08)',
    'border-radius-root': '12px',
    'medium-emphasis-opacity': 0.64,
    'hover-opacity': 0.04,
  },
}

export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'greenPilotTheme',
    themes: { greenPilotTheme },
  },
  defaults: {
    VCard: {
      elevation: 0,
      rounded: 'xl',
      border: 'sm opacity-12',
    },
    VBtn: {
      rounded: 'lg',
      elevation: 0,
    },
    VTextField: {
      variant: 'outlined',
      density: 'comfortable',
      color: 'primary',
      rounded: 'lg',
    },
    VSelect: {
      variant: 'outlined',
      density: 'comfortable',
      color: 'primary',
      rounded: 'lg',
    },
    VAutocomplete: {
      variant: 'outlined',
      density: 'comfortable',
      color: 'primary',
      rounded: 'lg',
    },
    VTextarea: {
      variant: 'outlined',
      density: 'comfortable',
      color: 'primary',
      rounded: 'lg',
    },
    VFileInput: {
      variant: 'outlined',
      density: 'comfortable',
      color: 'primary',
      rounded: 'lg',
    },
    VChip: {
      rounded: 'lg',
      size: 'small',
      variant: 'tonal',
    },
    VAlert: {
      rounded: 'lg',
      variant: 'tonal',
      border: 'start',
    },
    VTabs: {
      color: 'primary',
    },
    VTab: {
      rounded: 'lg',
    },
    VDialog: {
      transition: 'dialog-bottom-transition',
    },
    VList: {
      rounded: 'lg',
    },
    VListItem: {
      rounded: 'lg',
    },
    VNavigationDrawer: {
      elevation: 0,
    },
    VAppBar: {
      elevation: 0,
    },
    VDataTable: {
      hover: true,
      density: 'comfortable',
    },
    VProgressLinear: {
      rounded: true,
      roundedBar: true,
      height: 8,
    },
    VSnackbar: {
      rounded: 'lg',
      location: 'bottom right',
      timeout: 4000,
    },
    VPagination: {
      rounded: 'lg',
      activeColor: 'primary',
    },
    VDivider: {
      class: 'my-2 opacity-12',
    },
  },
})
