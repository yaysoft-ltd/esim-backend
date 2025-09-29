<?php

namespace App\Http\Controllers;

use App\Jobs\BatchUpdateInAppProductsJob;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Country;
use App\Models\Currency;
use App\Models\EsimPackage;
use App\Models\Page;
use App\Models\Region;
use App\Services\AiraloService;
use Carbon\Carbon;

class MasterController extends BaseController
{
    public function currencies()
    {
        try {
            $currencies = Currency::where('is_active', 1)->select('id', 'name', 'symbol')->get();
            return $this->sendResponse($currencies, 'Currencies data fetched');
        } catch (\Exception $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function countries()
    {
        try {
            $countries = Country::where('is_active', true)->get();
            $countries->map(function ($country) {
                $allPackages = $country->operators->flatMap->esimPackages;

                $prices = $allPackages->map(function ($pkg) {
                    $getPrice = packagePrice($pkg->id);
                    return ceil($getPrice['totalAmount']/5)*5 ?? null;
                })->filter();
                $country->start_price = $prices->min();
                unset($country->operators);

                return $country;
            });

            return $this->sendResponse($countries, 'Country data fetched');
        } catch (\Exception $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function regions()
    {
        try {
            $regions = Region::where('is_active', true)->get();
            $regions->map(function ($region) {
                $allPackages = $region->operators->flatMap->esimPackages;

                // Calculate prices for each package
                $prices = $allPackages->map(function ($pkg) {
                    $getPrice = packagePrice($pkg->id);
                    return ceil($getPrice['totalAmount']/5)*5 ?? null;
                })->filter();
                $region->start_price = $prices->min();
                unset($region->operators);

                return $region;
            });
            return $this->sendResponse($regions, 'Region data fetched');
        } catch (\Exception $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function pages()
    {
        try {
            $pages = Page::where('is_active', true)->get();
            return $this->sendResponse($pages, 'Pages data fetched successfully');
        } catch (\Exception $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function blogs()
    {
        try {
            $blogs = Blog::where('is_published', true)->get();
            return $this->sendResponse($blogs, 'Blogs Data fetched successfully');
        } catch (\Exception $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function banners()
    {
        try {
            $banners = Banner::where('is_active', true)->whereDate('banner_to', '>', Carbon::now())->get();
            return $this->sendResponse($banners, 'Data fetched successfully');
        } catch (\Exception $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function deviceCompatibleEsim(AiraloService $airalo)
    {
        try {
            $response = $airalo->deviceCompatible();
            return $this->sendResponse($response['data'], 'Data fetched successfully');
        } catch (\Exception $th) {
            return $this->sendError($th->getMessage());
        }
    }
}
