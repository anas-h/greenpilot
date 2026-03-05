import { createRouter, createWebHistory } from 'vue-router'
import { watch } from 'vue'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/auth/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/auth/RegisterView.vue'),
    meta: { guest: true },
  },
  {
    path: '/onboarding',
    name: 'onboarding',
    component: () => import('../views/auth/OnboardingView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/',
    component: () => import('../components/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', name: 'dashboard', component: () => import('../views/dashboard/DashboardView.vue') },
      { path: 'abonnement', name: 'abonnement', component: () => import('../views/subscription/SubscriptionView.vue') },
      { path: 'abonnement/checkout/:plan', name: 'checkout', component: () => import('../views/subscription/CheckoutView.vue') },
      { path: 'conteneurs', name: 'conteneurs', component: () => import('../views/conteneurs/ConteneursView.vue'), meta: { permission: 'conteneurs.view' } },
      { path: 'collecteurs', name: 'collecteurs', component: () => import('../views/collecteurs/CollecteursView.vue'), meta: { permission: 'collecteurs.view' } },
      { path: 'productions', name: 'productions', component: () => import('../views/productions/ProductionsView.vue'), meta: { permission: 'productions.view' } },
      { path: 'enlevements', name: 'enlevements', component: () => import('../views/enlevements/EnlevementsView.vue'), meta: { permission: 'enlevements.view' } },
      { path: 'bordereaux', name: 'bordereaux', component: () => import('../views/bsd/BsdView.vue'), meta: { permission: 'bordereaux.view' } },
      { path: 'registre', name: 'registre', component: () => import('../views/registre/RegistreView.vue'), meta: { permission: 'registre.view' } },
      { path: 'alertes', name: 'alertes', component: () => import('../views/alertes/AlertesView.vue'), meta: { permission: 'alertes.view' } },
      { path: 'conformite', name: 'conformite', component: () => import('../views/conformite/ConformiteView.vue'), meta: { permission: 'fds.view' } },
      { path: 'economie', name: 'economie', component: () => import('../views/economie/EconomieView.vue'), meta: { permission: 'economie.view' } },
      { path: 'parametres', name: 'parametres', component: () => import('../views/parametres/ParametresView.vue'), meta: { permission: 'garages.view' } },
      { path: 'profil', name: 'profil', component: () => import('../views/profil/ProfilView.vue') },
      // Admin routes
      { path: 'admin', redirect: '/admin/dashboard' },
      { path: 'admin/dashboard', name: 'admin-dashboard', component: () => import('../views/admin/AdminDashboardView.vue'), meta: { requiresSuperAdmin: true } },
      { path: 'admin/entreprises', name: 'admin-entreprises', component: () => import('../views/admin/AdminEntreprisesView.vue'), meta: { requiresSuperAdmin: true } },
      { path: 'admin/entreprises/:id', name: 'admin-entreprise-detail', component: () => import('../views/admin/AdminEntrepriseDetailView.vue'), meta: { requiresSuperAdmin: true } },
      { path: 'admin/bordereaux', name: 'admin-bordereaux', component: () => import('../views/admin/AdminBordereauxView.vue'), meta: { requiresSuperAdmin: true } },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Wait for auth initialization (checkAuth in App.vue) before first navigation
  if (!authStore.authReady) {
    await new Promise((resolve) => {
      const unwatch = watch(() => authStore.authReady, (ready) => {
        if (ready) { unwatch(); resolve() }
      }, { immediate: true })
    })
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login' })
  }
  if (to.meta.guest && authStore.isAuthenticated) {
    return next({ name: 'dashboard' })
  }
  if (to.meta.requiresSuperAdmin && authStore.user?.role !== 'super_admin') {
    return next({ name: 'dashboard' })
  }
  if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
    return next({ name: 'dashboard' })
  }
  next()
})

export default router
