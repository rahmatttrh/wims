<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('logos/icon.jpg') }}">
    <title>Inbound Import by Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  </head>
  <body>
    <div class="container pt-4">

      <div class="card shadow mt-4">
         <div class="card-header">
            Form Import Inbound by Excel
         </div>
         <div class="card-body">
            <div class="row">
               <div class="col-md-8">
                  <form action="{{ route('checkins.import.store') }}" method="POST" enctype="multipart/form-data">
                     @csrf
                     <div class="mb-3">
                         <label for="file" class="form-label">Pilih File Excel</label>
                         <input type="file" name="file" id="file" class="form-control" accept=".xls,.xlsx">
                         @error('file')
                             <div class="text-danger small">{{ $message }}</div>
                         @enderror
                     </div>
                 
                     <button type="submit" class="btn btn-primary">Import</button>
                 </form>
               </div>
               <div class="col-md-4">
                  <div class="card">
                     <div class="card-body">
                        <a href="/template/template-import-inbound.xlsx" class="btn btn-primary">Download Template</a>
                        <hr>
                        <small>Klik tombol diatas untuk mengunduh Template Import Excel</small>
                     </div>
                  </div>
                  
               </div>
            </div>
            
         </div>
         <div class="card-footer">
            <small>Ekanuri Development</small>
         </div>
      </div>
      
    </div>
  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  </body>
</html>