<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Enums\Responses;

trait ApiResponse
{

    /**
     * Return HTTP Continue (100)
     *
     * @return \Illuminate\Http\Response
     */
    public function continues()
    {
        $args = func_get_args();
        return $this->response($this->getData($args), Responses::HTTP_CONTINUE);
    }

    /**
     * Return HTTP Success (200)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function success()
    {
        $args = func_get_args();
        return $this->response($this->getData($args), Responses::HTTP_OK);
    }

    /**
     * Return HTTP Created (201)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function created()
    {
        $args = func_get_args();
        return $this->response($this->getData($args), Responses::HTTP_CREATED);
    }

    /**
     * Return HTTP Bad Request (400)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bad()
    {
        $args = func_get_args();
        return $this->response($this->getData($args), Responses::HTTP_BAD_REQUEST);
    }

    /**
     * Return HTTP Unauthorized (401)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unauthorized()
    {
        $args = func_get_args();
        return $this->response($this->getData($args), Responses::HTTP_UNAUTHORIZED);
    }

    /**
     * Return HTTP Not Found (404)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function notFound()
    {
        $args = func_get_args();
        return $this->response($this->getData($args), Responses::HTTP_NOT_FOUND);
    }

    /**
     * Return HTTP Conflict (409)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function conflict()
    {
        $args = func_get_args();
        return $this->response($this->getData($args), Responses::HTTP_CONFLICT);
    }

    /**
     * Return HTTP Bad Request (422)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unprocessable()
    {
        $args = func_get_args();
        return $this->response($this->getData($args), Responses::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Return HTTP Internal Error (500)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function internalError()
    {
        $args = func_get_args();
        return $this->response($this->getData($args), Responses::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Return HTTP Code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function response(array $data, $http_code)
    {
        if (config('odin.queryRequest')) {
            $data['queries'] = $this->getQueries();
        }
        return response()->json($data, $http_code);
    }

    /**
     * Return Exception into HTTP Internal Error (500)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function exception(\Exception $ex)
    {
        $log = [
            'error' => $ex->getMessage(),
            'file' => $ex->getFile(),
            'line' => $ex->getLine()
        ];

        Log::error($log);

        $return = [
            "erro" => "Ocorreu um erro inesperado. Por favor, tente mais tarde."
        ];

        if (App::environment('local', 'staging')) {
            $return['server_error'] = $log;
        }

        return $this->internalError($return);
    }

    /**
     * Return entity data array or array blank
     *
     * @return array
     */
    private function getData($args)
    {
        $data = [];

        /* Sem argumentos, retorna array em branco */
        if (count($args) < 1) {
            return $data;
        }

        /* Enviou um array como parâmetro */
        if (is_array($args[0])) {
            return $args[0];
        }

        /* Enviou um Paginador como parâmetro */
        if (is_a($args[0], LengthAwarePaginator::class)) {
            $paginator = ($args[0]);
            $data = $paginator->toArray();
        }

        /* Enviou uma Coleção como parâmetro */
        if (is_a($args[0], Collection::class)) {
            $collection = ($args[0]);
            $data = $collection->toArray();
        }

        /* Enviou uma Entidade como parâmetro */
        if (is_a($args[0], Model::class)) {
            $entity = ($args[0]);
            // $entity->autoload();
            // $data['data'] = $entity->toArray();
            $data = $entity->toArray();
        }

        return $data;
    }

    /**
     * Return entity data array or array blank
     *
     * @return array
     */
    private function getQueries()
    {
        return $queries = DB::getQueryLog();

        $formattedQueries = [];
        foreach ($queries as $query) {
            $prep = $query['query'];

            foreach ($query['bindings'] as $binding) {
                $prep = preg_replace("#\?#", $binding, $prep, 1);
            }

            $formattedQueries[] = $prep;
        }

        return $formattedQueries;
    }
}
