<?php
    namespace Dearmadman\ImageTool;

    use Illuminate\Support\ServiceProvider;
    class ImageToolServiceProvider extends ServiceProvider
    {

        public function register ()
        {

            $this->app->singleton('DearMadMan\ImageTool',function($app){
                return ImageTool::GetInstance($app['options']);
            });
        }
    }

