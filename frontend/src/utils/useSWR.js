import { ref } from 'vue';

/**
 * useSWR - Stale-While-Revalidate untuk Vue 3
 * @param {string} cacheKey - Kunci unik untuk LocalStorage
 * @param {function} fetcher - Fungsi async yang mereturn response API
 * @param {any} initialData - Nilai awal (default: null atau [])
 * @returns { data, isLoading, error, revalidate }
 */
export function useSWR(cacheKey, fetcher, initialData = null) {
  const data = ref(initialData);
  const isLoading = ref(true);
  const error = ref(null);

  let isCached = false;
  
  // Waktu kadaluarsa cache (24 jam)
  const TTL = 24 * 60 * 60 * 1000;
  let cacheTimestamp = 0;

  try {
    const cached = localStorage.getItem(cacheKey);
    const cachedTime = localStorage.getItem(`${cacheKey}_timestamp`);
    
    if (cached) {
      data.value = JSON.parse(cached);
      isLoading.value = false; // Jika ada cache, langsung tampil (0 detik)
      isCached = true;
      
      if (cachedTime) {
        cacheTimestamp = parseInt(cachedTime, 10);
      }
    }
  } catch (e) {
    console.error("Gagal membaca cache:", e);
  }

  const revalidate = async () => {
    try {
      // Jika cache masih ada dan umurnya kurang dari 24 jam, TIDAK PERLU fetch ke backend
      const now = Date.now();
      if (isCached && (now - cacheTimestamp < TTL)) {
        // Hentikan fungsi, hemat kuota Render! Tidak perlu memanggil API.
        return; 
      }
      if (!isCached) isLoading.value = true;

      const response = await fetcher();
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

      const responseData = await response.json();
      const freshData = responseData.data !== undefined ? responseData.data : responseData;
      
      const freshString = JSON.stringify(freshData);
      const oldString = localStorage.getItem(cacheKey);
      
      // Update reaktif HANYA jika data benar-benar berubah
      if (freshString !== oldString) {
        data.value = freshData;
        localStorage.setItem(cacheKey, freshString);
      }
      
      // Selalu update timestamp setiap kali sukses fetch
      localStorage.setItem(`${cacheKey}_timestamp`, Date.now().toString());
    } catch (err) {
      error.value = err;
      console.error(`SWR Error [${cacheKey}]:`, err);
    } finally {
      isLoading.value = false;
    }
  };

  // Jalankan revalidasi di latar belakang
  revalidate();

  return { data, isLoading, error, revalidate };
}
