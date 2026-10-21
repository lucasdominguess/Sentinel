
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{asset('assets/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
     <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"> -->
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        thead { background-color:rgba(113, 113, 131, 0.45); color: #fff; border: none; padding: 8px 16px; cursor: pointer; }

        /* th { background-color: #f2f2f2; } */
        </style>
        @yield('head')
</head>
<body>

    @yield('content')
</body>
</html>
