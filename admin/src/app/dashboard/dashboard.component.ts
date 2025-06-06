import { Component, OnInit } from '@angular/core';
import { AdminService } from '../_services/admin.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  constructor(private adminService: AdminService) { }

  countersList: any[];
  counterBg: any[];
  counterColor: any[];
  counterUrl: any[];
  counterImg: any[];

  inquiry_ststus_counter: any = {

  };

  // inquiryStatusCounter() {
  //   this.adminService.inquiryStatusCounter({}).subscribe((response: any) => {
  //     if (response.success) {
  //       this.inquiry_ststus_counter = response.data;
  //       // console.log(response.data);
  //     }
  //     else {
  //       this.inquiry_ststus_counter = {}
  //     }
  //   })
  // }

  user_type_counter: any = {};

  // userStatusCounter() {
  //   this.adminService.userTypeCounter({}).subscribe((res: any) => {
  //     if (res.success) {
  //       this.user_type_counter = res.data;
  //       console.log(res.data);
  //     }
  //     else {
  //       this.user_type_counter = {}
  //     }
  //   })
  //   // console.log(this.user_type_counter);
  // }

  ngOnInit() {
    // this.adminService.getCounters().subscribe((res) => {
    //   if (res.success) {
    //     this.countersList = res.list;
    //   }
    //   this.counterBg = ['#C4F7ED', '#E7EDFF', '#FEE3E6', '#E3F4FE', '#FFEAC7']
    //   this.counterColor = ['#13DEB9', '#5D87FF', '#FA896B', '#44B7F7', '#FFAE1F']
    //   this.counterUrl = ['list-form', 'users', 'inquiry', '', '']
    //   this.counterImg = ['exam.png', 'icon-user-male.svg', 'inquiry.png', '', '']
    // })

    // this.inquiryStatusCounter();
    // this.userStatusCounter();
    // console.log(this.inquiry_ststus_counter);

    // 
    this.dashboardcounters();
  }


  // add++

  form_counters: any = {}

  dashboardcounters() {
    this.adminService.dashboardCounters({}).subscribe((response: any) => {
      if (response.success == 1) {
        this.inquiry_ststus_counter = response.data.inquiries_counter;
        this.user_type_counter = response.data.users_conter;
        this.form_counters = response.data.form_counter;
      }
    })
  }




}
