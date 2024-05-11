<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\EventStoreRequest;
use App\Http\Requests\Event\EventUpdateRequest;
use App\Services\EventService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class EventController extends Controller
{
    private EventService $service;

    /**
     * @param EventService $service
     */
    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    /**
     * [MASTER]
     * MOSTRA OS EVENTO CADASTRADOS NA PLATAFORMA
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $page = $request->input('page', 1);

        $search = $request->input('search', '');
        $status = $request->input('status', null);
        $producer = $request->input('producer', null);

        $params = [
            'status' => $status,
            'search' => $search,
            'producer' => $producer
        ];

        return $this->service->index($paginate, $page, $params);
    }

    /**
     * [CLIENTE]
     * USADO PARA MOSTRAR EVENTTOS FILTRADOS PELO CLIENTE
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $page = $request->input('page', 1);
        $search = $request->input('search', '');
        $city = $request->input('city', null);
        $producer = $request->input('producer', null);
        $category = $request->input('category', null);
        $period = $request->input('period', null);

        $params = [
            'city' => $city,
            'search' => $search,
            'producer' => $producer,
            'category' => $category,
            'period' => $period,
        ];

        return $this->service->search($paginate, $page, $params);
    }

    /**
     * [CLIENTE]
     * MOSTRA OS 18 EVENTOS DA TELA INCIAL DE ACORDO COM A REGRA DE IMPULSIONAMENTO
     * @param Request $request
     * @return JsonResponse
     */
    public function panel(Request $request): JsonResponse
    {
        $params = $request->input('params', 0);
        return $this->service->panel($params);
    }

    /**
     *  [PRODUTOR]
     *  MOSTRA OS DADOS DO ENVETO
     * @param string $hash
     * @return JsonResponse
     */
    public function show(string $hash): JsonResponse
    {
        return $this->service->show($hash);
    }

    /**
     * [CLIENTE]
     * MOSTRA OS DETALHES DO EVENTO PARA O CLIENTE
     * @param string $slug
     * @return JsonResponse
     */
    public function details(string $slug): JsonResponse
    {
        return $this->service->details($slug);
    }

    /**
     * [PRODUTOR]
     * MOSTRA OS EVENTO CADASTRADOS DO PRODUTOR LOGADO
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        return $this->service->list($paginate, $page, $search);
    }

    /**
     * [PRODUTOR]
     * MOSTRA OS EVENTO CADASTRADOS DO PRODUTOR LOGADO
     * @param Request $request
     * @return JsonResponse
     */
    public function listActive(): JsonResponse
    {
        return $this->service->listActive();
    }

    /**
     * [ADM MASTER]
     * MOSTRA OS GERAIS DOS EVENTOS CADASTRADOS PELO PRODUTOR LOGADO
     * @param Request $request
     * @return JsonResponse
     */
    public function emphasis(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $page = $request->input('page', 1);
        $search = $request->input('search', '');

        $params = [
            'search' => $search
        ];

        return $this->service->emphasis($paginate, $page, $params);
    }

    /**
     * [PRODUTOR]
     * MOSTRA OS GERAIS DOS EVENTOS CADASTRADOS PELO PRODUTOR LOGADO
     * @param string $event
     * @return JsonResponse
     */
    public function dashboard(string $event): JsonResponse
    {
        return $this->service->dashboard($event);
    }

    /**
     * [ADM MASTER]
     * MOSTRA OS EVENTOS QUE ESTÃO COM ALGUM TIPO DE DESTAQUE
     * @param string $event
     * @return JsonResponse
     */
    public function dashboardAdm(string $event): JsonResponse
    {
        return $this->service->dashboardAdm($event);
    }

    /**
     * [ADM MASTER]
     * @param string $event
     * @return JsonResponse
     */
    public function best(string $event): JsonResponse
    {
        return $this->service->best($event);
    }

    /**
     * [PRODUTOR]
     * MOSTRA OS GERAIS DOS EVENTOS CADASTRADOS PELO PRODUTOR LOGADO
     * @param string $event
     * @return JsonResponse
     */
    public function donwloadSales(string $event): JsonResponse
    {
        return $this->service->donwloadSales($event);
    }

    /**
     * @param int $mapId
     * @return JsonResponse
     */
    public function mapTickets(?int $mapId = null): JsonResponse
    {
        return $this->service->mapTickets($mapId);
    }

    /**
     * @param EventStoreRequest $request
     * @return JsonResponse
     */
    public function store(EventStoreRequest $request): JsonResponse
    {
        return $this->service->store($request->all());
    }

    /**
     * @param EventUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(EventUpdateRequest $request, int $id): JsonResponse
    {
        return $this->service->update($id, $request->all());
    }

    /**
     * @param EventUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function canceled(EventUpdateRequest $request, int $id): JsonResponse
    {
        return $this->service->canceled($id, $request->all());
    }

    /**
     * @param EventUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function promote(EventUpdateRequest $request, int $id): JsonResponse
    {
        return $this->service->promote($id, $request->all());
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        return $this->service->delete($id);
    }
}
