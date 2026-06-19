<?php
/**
 * FAXEL BI — Controlador de Productos / Rentabilidad (Módulo 4) SaaS Multiempresa
 */
class ProductoController extends Controller
{
    private ProductoModel $productoModel;

    public function __construct()
    {
        parent::__construct();
        $this->productoModel = new ProductoModel();
    }

    public function rentabilidad(array $params = []): void
    {
        $this->requireAuth();
        $empresaId = $this->getEmpresaId();

        // Actualizar clasificación ABC
        $this->productoModel->actualizarClasificacionABC($empresaId);

        $topRentables  = $this->productoModel->topRentables($empresaId, 20);
        $topCrecimiento= $this->productoModel->topCrecimiento($empresaId, 10);
        $distABC       = $this->productoModel->distribucionABC($empresaId);
        $paretoData    = $this->productoModel->paretoData($empresaId);

        // Cálculo Pareto acumulado
        $totalUtilidad = array_sum(array_column($paretoData, 'utilidad'));
        $acumulado     = 0;
        $paretoAcum    = [];
        foreach ($paretoData as $p) {
            $acumulado += $p['utilidad'];
            $paretoAcum[] = $totalUtilidad > 0
                ? round(($acumulado / $totalUtilidad) * 100, 1)
                : 0;
        }

        $this->view('productos/rentabilidad', [
            'title'          => 'Rentabilidad de Productos',
            'topRentables'   => $topRentables,
            'topCrecimiento' => $topCrecimiento,
            'distABC'        => $distABC,
            'paretoData'     => $paretoData,
            'paretoAcum'     => $paretoAcum,
        ]);
    }
}
