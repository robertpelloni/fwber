<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>FWBer API</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <style>
            body { font-family: 'Instrument Sans', sans-serif; }
        </style>
    </head>
    <body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">
        <div class="max-w-md w-full bg-gray-800 rounded-lg shadow-xl overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-center mb-6">
                    <div class="bg-blue-600 p-3 rounded-full">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-center mb-2">FWBer API</h1>
                <p class="text-gray-400 text-center mb-6">Backend Services Online</p>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded">
                        <span class="text-gray-300">Status</span>
                        <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Operational</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded">
                        <span class="text-gray-300">Environment</span>
                        <span class="text-white font-mono text-sm"><?php echo e(app()->environment()); ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded">
                        <span class="text-gray-300">Laravel Version</span>
                        <span class="text-white font-mono text-sm"><?php echo e(app()->version()); ?></span>
                    </div>
                </div>

                <div class="mt-8 text-center">
                    <a href="/api/documentation" class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors">
                        View API Documentation &rarr;
                    </a>
                </div>
            </div>
            <div class="bg-gray-700 px-6 py-4">
                <p class="text-xs text-center text-gray-500">
                    &copy; <?php echo e(date('Y')); ?> FWBer. All rights reserved.
                </p>
            </div>
        </div>
    </body>
</html>
<?php /**PATH C:\Users\hyper\workspace\fwber\fwber-backend\resources\views/welcome.blade.php ENDPATH**/ ?>