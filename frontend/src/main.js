import { createApp } from "vue";
import "./style.css";
import App from "./App.vue";
import { createRouter, createWebHistory } from "vue-router";
import NProgress from "nprogress";
import "nprogress/nprogress.css";
import Homepage from "./components/Homepage/Homepage.vue";
import DashboardAdmin from "./components/LayoutAdmin/DashboardAdmin.vue";
import AdminDashboard from "./components/Admin/Pages/AdminDashboard.vue";
import AdminLogin from "./components/Admin/AdminLogin.vue";
import AdminLogout from "./components/Admin/AdminLogout.vue";
import AdminSkillsList from "./components/Admin/Pages/Skill/AdminSkillList.vue";
import AdminProjectList from "./components/Admin/Pages/Project/AdminProjectList.vue";
import AdminUploadOrUpdateProject from "./components/Admin/Pages/Project/AdminUploadOrUpdateProject.vue";
import AdminContactList from "./components/Admin/Pages/Contact/AdminContactList.vue";
import AdminUploadOrUpdateProfile from "./components/Admin/Pages/Profile/AdminUploadOrUpdateProfile.vue";
import AdminCertificateList from "./components/Admin/Pages/Certificate/AdminCertificateList.vue";
import AdminUploadOrUpdateCertificate from "./components/Admin/Pages/Certificate/AdminUploadOrUpdateCertificate.vue";
import AdminExperienceList from "./components/Admin/Pages/Experience/AdminExperienceList.vue";
import AdminServiceList from "./components/Admin/Pages/Service/AdminServiceList.vue";
import NotFound from "./components/NotFound.vue";
import AllProjects from "./components/Project/AllProjects.vue";
import AllCertificates from "./components/Certificate/AllCertificates.vue";
import AllContacts from "./components/Contact/AllContacts.vue";
import Home from "./components/LayoutHome/Home.vue";
import AllServices from "./components/Service/AllServices.vue";
import About from "./components/About/About.vue";

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior(to, from, savedPosition) {
    // Jika ada savedPosition (contoh: user klik tombol back/forward di browser),
    // kembalikan ke posisi sebelumnya
    if (savedPosition) {
      return savedPosition;
    } else {
      // Jika navigasi baru (pindah menu navbar), paksa scroll ke paling atas
      // Native smooth scroll conflicts with Lenis custom scroll hijacking.
      return { top: 0 };
    }
  },
  routes: [
    {
      path: "/",
      component: Home,
      children: [
        {
          path: "/",
          component: Homepage,
        },
        {
          path: "/about",
          component: About,
        },
        {
          path: "/projects",
          component: AllProjects,
        },
        {
          path: "/certificates",
          component: AllCertificates,
        },
        {
          path: "/services",
          component: AllServices,
        },
        {
          path: "/contacts",
          component: AllContacts,
        },
      ],
    },

    {
      path: "/admin",
      redirect: "/admin/login",
    },
    {
      path: "/admin/login",
      component: AdminLogin,
      meta: { guestOnly: true },
    },
    {
      path: "/admin/dashboard",
      component: DashboardAdmin,
      meta: { requiresAuth: true },
      children: [
        {
          path: "",
          component: AdminDashboard,
        },
        {
          path: "logout",
          component: AdminLogout,
        },

        //projects
        {
          path: "projects",
          component: AdminProjectList,
        },
        {
          path: "projects/create",
          component: AdminUploadOrUpdateProject,
        },
        {
          path: "projects/edit/:id",
          component: AdminUploadOrUpdateProject,
        },

        //certificate
        {
          path: "certificates",
          component: AdminCertificateList,
        },
        {
          path: "certificates/create",
          component: AdminUploadOrUpdateCertificate,
        },
        {
          path: "certificates/edit/:id",
          component: AdminUploadOrUpdateCertificate,
        },

        //contact
        {
          path: "contacts",
          component: AdminContactList,
        },

        //experience
        {
          path: "experiences",
          component: AdminExperienceList,
        },

        //skills
        {
          path: "skills",
          component: AdminSkillsList,
        },

        //service
        {
          path: "services",
          component: AdminServiceList,
        },

        //profile
        {
          path: "profile",
          component: AdminUploadOrUpdateProfile,
        },
      ],
    },

    //notfount
    {
      path: "/:pathMatch(.*)*",
      component: NotFound,
    },
  ],
});

// [BEST PRACTICE] Global Navigation Guard
router.beforeEach((to, from, next) => {
  NProgress.start();
  // Ambil token dari localStorage (sesuaikan key-nya dengan kode login Anda)
  const token = localStorage.getItem("token");

  // 1. Cek apakah route tujuan butuh Autentikasi
  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (!token) {
      // Tidak ada token? Tendang ke login
      next({ path: "/admin/login" });
    } else {
      // Ada token? Silakan lanjut
      next();
    }
  }
  // 2. Cek apakah route tujuan khusus Guest (Login page)
  else if (to.matched.some((record) => record.meta.guestOnly)) {
    if (token) {
      // Sudah login tapi mau ke page login? Balikin ke dashboard
      next({ path: "/admin/dashboard" });
    } else {
      next();
    }
  }
  // 3. Route umum (Homepage, 404, dll)
  else {
    next();
  }
});

// Force global scrolling systems like Lenis to update scroll state after navigation
router.afterEach((to, from) => {
  NProgress.done();
  // Only execute scroll jump if the route actually changed (avoids triggering on modal pushState/popState)
  if (to.path !== from.path) {
    // Reset window position manually just in case
    window.scrollTo(0, 0);
    
    // If Lenis is instantiated globally, instantly teleport it to 0 otherwise it will resume its inertia from the previous page
    if (window.lenis) {
      window.lenis.scrollTo(0, { immediate: true });
      
      // FIX ADMIN SCROLL: Stop lenis from intercepting scroll if we are in admin
      if (to.path.startsWith('/admin')) {
        window.lenis.stop();
      } else {
        window.lenis.start();
      }
    }
  }
});

createApp(App).use(router).mount("#app");
