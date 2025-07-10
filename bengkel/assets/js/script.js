// Fungsi untuk konfirmasi hapus
function confirmDelete(message) {
    return confirm(message || "Apakah Anda yakin ingin menghapus data ini?");
}

// Fungsi untuk auto-fill harga saat memilih sparepart
document.addEventListener('DOMContentLoaded', function() {
    const sparepartSelect = document.getElementById('id_sparepart');
    const hargaInput = document.getElementById('harga_satuan');
    
    if (sparepartSelect && hargaInput) {
        sparepartSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                hargaInput.value = selectedOption.getAttribute('data-harga');
            }
        });
    }
    
    // Toggle form jasa/sparepart
    const tipeItemSelect = document.getElementById('tipe_item');
    if (tipeItemSelect) {
        tipeItemSelect.addEventListener('change', function() {
            toggleItemForm();
        });
    }
});

// Fungsi untuk toggle form sparepart/jasa
function toggleItemForm() {
    const tipeItem = document.getElementById('tipe_item').value;
    const sparepartForm = document.getElementById('sparepart_form');
    const jasaForm = document.getElementById('jasa_form');
    const namaJasaInput = document.getElementById('nama_jasa');
    const sparepartSelect = document.getElementById('id_sparepart');
    
    if (tipeItem === 'sparepart') {
        sparepartForm.style.display = 'block';
        jasaForm.style.display = 'none';
        if (namaJasaInput) namaJasaInput.value = '';
    } else {
        sparepartForm.style.display = 'none';
        jasaForm.style.display = 'block';
        if (sparepartSelect) sparepartSelect.value = '';
    }
}

// Fungsi untuk pencarian
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('dataTable');
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let found = false;
        const td = tr[i].getElementsByTagName('td');
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        if (found) {
            tr[i].style.display = '';
        } else {
            tr[i].style.display = 'none';
        }
    }
}

// Fungsi untuk menghitung subtotal
function hitungSubtotal() {
    const jumlah = document.getElementById('jumlah').value;
    const hargaSatuan = document.getElementById('harga_satuan').value;
    const subtotalElement = document.getElementById('subtotal');
    
    if (jumlah && hargaSatuan && subtotalElement) {
        const subtotal = jumlah * hargaSatuan;
        subtotalElement.textContent = formatRupiah(subtotal);
    }
}

// Fungsi untuk format rupiah
function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}